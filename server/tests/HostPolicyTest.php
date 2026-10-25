<?php

namespace Fleetbase\Vroom\Tests;

use Fleetbase\Vroom\Support\HostPolicy;
use PHPUnit\Framework\TestCase;

class HostPolicyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        HostPolicy::resolveHostsUsing(fn (string $host) => [
            'routing.example.com'  => ['93.184.216.34', '2606:2800:220:1:248:1893:25c8:1946'],
            'internal.example.com' => ['10.0.0.12'],
            'mixed.example.com'    => ['93.184.216.34', '169.254.169.254'],
            'mapped.example.com'   => ['::ffff:127.0.0.1'],
        ][$host] ?? []);
    }

    protected function tearDown(): void
    {
        HostPolicy::resolveHostsUsing(null);
        config(['vroom.allow_private_hosts' => false]);

        parent::tearDown();
    }

    public static function allowedUrls(): array
    {
        return [
            'public hostname'      => ['https://routing.example.com/vrp/v1'],
            'http with port'       => ['http://routing.example.com:8002'],
            'public IPv4 literal'  => ['https://93.184.216.34'],
            'public IPv6 literal'  => ['https://[2606:4700:4700::1111]/route'],
        ];
    }

    /**
     * @dataProvider allowedUrls
     */
    public function testAllowsPublicHttpHosts(string $url): void
    {
        $this->assertNull(HostPolicy::violation($url));
    }

    public static function rejectedUrls(): array
    {
        return [
            'loopback'                 => ['http://127.0.0.1:8002'],
            'localhost IPv6'           => ['http://[::1]:8002'],
            'cloud metadata'           => ['http://169.254.169.254/latest/meta-data'],
            'private 10/8'             => ['http://10.1.2.3'],
            'private 172.16/12'        => ['http://172.20.0.5'],
            'private 192.168/16'       => ['http://192.168.1.1'],
            'carrier-grade NAT'        => ['http://100.64.0.1'],
            'unspecified'              => ['http://0.0.0.0'],
            'multicast'                => ['http://224.0.0.1'],
            'unique local IPv6'        => ['http://[fd00::1]'],
            'link-local IPv6'          => ['http://[fe80::1]'],
            'IPv4-mapped loopback'     => ['http://[::ffff:127.0.0.1]'],
            'NAT64 loopback'           => ['http://[64:ff9b::7f00:1]'],
            'resolves to private'      => ['https://internal.example.com'],
            'one address is private'   => ['https://mixed.example.com'],
            'resolves to mapped'       => ['https://mapped.example.com'],
            'does not resolve'         => ['https://nowhere.example.com'],
            'file scheme'              => ['file:///etc/passwd'],
            'gopher scheme'            => ['gopher://routing.example.com'],
            'no scheme'                => ['routing.example.com'],
            'credentials'              => ['https://user:pass@routing.example.com'],
            'not a URL'                => ['http://'],
        ];
    }

    /**
     * @dataProvider rejectedUrls
     */
    public function testRejectsHostsTheServerMustNotCall(string $url): void
    {
        $this->assertNotNull(HostPolicy::violation($url));
    }

    public function testAllowPrivateHostsLetsSelfHostedInstallsUsePrivateAddresses(): void
    {
        config(['vroom.allow_private_hosts' => true]);

        $this->assertNull(HostPolicy::violation('http://vroom:3000'));
        $this->assertNull(HostPolicy::violation('http://10.0.0.12:8002'));
        $this->assertNotNull(HostPolicy::violation('file:///etc/passwd'));
        $this->assertNotNull(HostPolicy::violation('http://user:pass@10.0.0.12'));
    }
}
