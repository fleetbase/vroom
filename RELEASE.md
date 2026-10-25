> v0.0.5 ~ "Locked-down settings and release CI"

---
## Highlights

- **Security: the VROOM settings are locked down.** Before, any logged-in user could read the system API key and change the system host and endpoint mode used by every organization. That could send every organization's optimization payloads (orders, addresses, vehicles) to another server.
  - Only system administrators can read or change the system settings.
  - Organization settings need `fleet-ops update routing-settings`, or `view routing-settings` to read them.
  - The API key is never sent back to the console. A blank key keeps the stored one, and "Remove the saved API key" clears it. (#8)
- **Security: organization hosts must be public addresses.** A host that points at loopback, a private network or cloud metadata is refused on save. If one was saved earlier, it is ignored at request time and the system server is used. `VROOM_ALLOW_PRIVATE_HOSTS=true` turns this off for installs that run VROOM on their own network. (#9)
- **Release CI like Fleet-Ops.**
  - PRs into `release/v*` run the Ember and PHP CI.
  - The Ember CI lints.
  - A `pnpm-workspace.yaml` (`allowBuilds`) fixes `ERR_PNPM_IGNORED_BUILDS` under pnpm 11, which had failed every Ember CI run on `main`.
  - PHP tests run again (#8).

---
## Upgrading
- No migrations.
- Organization settings now need the Fleet-Ops `routing-settings` permissions. Run `php artisan fleetbase:create-permissions` if they aren't seeded.

---
## Need help?
- [GitHub Discussions](https://github.com/fleetbase/fleetbase/discussions)
- [Discord](https://discord.gg/HnTqQ6zAVn)
