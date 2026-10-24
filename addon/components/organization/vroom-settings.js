import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { debug } from '@ember/debug';
import { task } from 'ember-concurrency';

export default class OrganizationVroomSettingsComponent extends Component {
    @service fetch;
    @service notifications;
    @tracked useOwnServer = false;
    @tracked apiKey;
    @tracked apiHost;
    @tracked endpointMode = 'saas';
    @tracked hasApiKey = false;
    @tracked clearApiKey = false;

    get saveTaskKey() {
        return 'organization:vroom-settings';
    }

    constructor(owner, args) {
        super(owner, args);
        args?.controller?.registerSaveTask(this.saveTaskKey, this.saveVroomSettings);
        this.loadVroomSettings.perform();
    }

    willDestroy() {
        super.willDestroy(...arguments);
        this.controller?.unregisterSaveTask(this.saveTaskKey);
    }

    get controller() {
        return this.args.controller;
    }

    get isActive() {
        return this.controller?.optimizationEngine === 'vroom';
    }

    @task *loadVroomSettings() {
        try {
            const { api_host, endpoint_mode, has_api_key } = yield this.fetch.get('settings', {}, { namespace: 'vroom/int/v1' });
            this.apiKey = null;
            this.apiHost = api_host;
            this.endpointMode = endpoint_mode ?? 'saas';
            this.hasApiKey = Boolean(has_api_key);
            this.clearApiKey = false;
            this.useOwnServer = Boolean(
                this.hasApiKey ||
                    (typeof api_host === 'string' && api_host.trim() !== '') ||
                    (typeof endpoint_mode === 'string' && endpoint_mode.trim() !== '')
            );
        } catch (err) {
            debug(`VROOM : Error fetching organization vroom settings: ${err.message}`);
        }
    }

    @task *saveVroomSettings() {
        if (!this.isActive) {
            return true;
        }

        // The stored key is never sent to the console: a blank key keeps it, `clear_api_key` removes it.
        const clearApiKey = !this.useOwnServer || this.clearApiKey;

        try {
            yield this.fetch.post(
                'settings',
                {
                    api_key: clearApiKey ? null : this.apiKey,
                    clear_api_key: clearApiKey,
                    api_host: this.useOwnServer ? this.apiHost : null,
                    endpoint_mode: this.useOwnServer ? this.endpointMode : null,
                },
                { namespace: 'vroom/int/v1' }
            );
            yield this.loadVroomSettings.perform();
        } catch (err) {
            debug(`VROOM : Error saving organization vroom settings: ${err.message}`);
            this.notifications.serverError(err);
        }
    }
}
