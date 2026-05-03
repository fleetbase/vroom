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
            const { api_key, api_host, endpoint_mode } = yield this.fetch.get('settings', {}, { namespace: 'vroom/int/v1' });
            this.apiKey = api_key;
            this.apiHost = api_host;
            this.endpointMode = endpoint_mode ?? 'saas';
            this.useOwnServer = Boolean(
                (typeof api_key === 'string' && api_key.trim() !== '') ||
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

        try {
            yield this.fetch.post(
                'settings',
                {
                    api_key: this.useOwnServer ? this.apiKey : null,
                    api_host: this.useOwnServer ? this.apiHost : null,
                    endpoint_mode: this.useOwnServer ? this.endpointMode : null,
                },
                { namespace: 'vroom/int/v1' }
            );
        } catch (err) {
            debug(`VROOM : Error saving organization vroom settings: ${err.message}`);
            this.notifications.serverError(err);
        }
    }
}
