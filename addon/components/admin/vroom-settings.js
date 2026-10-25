import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { debug } from '@ember/debug';
import { task } from 'ember-concurrency';

export default class AdminVroomSettingsComponent extends Component {
    @service fetch;
    @service notifications;
    @tracked apiKey;
    @tracked apiHost;
    @tracked endpointMode = 'saas';
    @tracked hasApiKey = false;
    @tracked hasEnvironmentApiKey = false;
    @tracked clearApiKey = false;

    constructor() {
        super(...arguments);
        this.loadVroomSettings.perform();
    }

    get apiKeyHelpText() {
        if (this.hasApiKey) {
            return 'A system API key is saved. It is never shown; leave this blank to keep it.';
        }

        if (this.hasEnvironmentApiKey) {
            return 'Using the API key from the server environment (VROOM_API_KEY). Enter a key to override it.';
        }

        return 'Optional API key for the system-wide VROOM service.';
    }

    @task *loadVroomSettings() {
        try {
            const { api_host, endpoint_mode, has_api_key, has_environment_api_key } = yield this.fetch.get('admin-settings', {}, { namespace: 'vroom/int/v1' });
            this.apiKey = null;
            this.apiHost = api_host;
            this.endpointMode = endpoint_mode ?? 'saas';
            this.hasApiKey = Boolean(has_api_key);
            this.hasEnvironmentApiKey = Boolean(has_environment_api_key);
            this.clearApiKey = false;
        } catch (err) {
            debug(`VROOM : Error fetching admin vroom settings: ${err.message}`);
        }
    }

    @task *saveVroomSettings() {
        try {
            yield this.fetch.post(
                'admin-settings',
                {
                    api_key: this.clearApiKey ? null : this.apiKey,
                    clear_api_key: this.clearApiKey,
                    api_host: this.apiHost,
                    endpoint_mode: this.endpointMode,
                },
                { namespace: 'vroom/int/v1' }
            );
            this.notifications.success('VROOM settings saved.');
            yield this.loadVroomSettings.perform();
        } catch (err) {
            debug(`VROOM : Error saving admin vroom settings: ${err.message}`);
            this.notifications.serverError(err);
        }
    }
}
