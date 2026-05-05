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

    constructor() {
        super(...arguments);
        this.loadVroomSettings.perform();
    }

    @task *loadVroomSettings() {
        try {
            const { api_key, api_host, endpoint_mode } = yield this.fetch.get('admin-settings', {}, { namespace: 'vroom/int/v1' });
            this.apiKey = api_key;
            this.apiHost = api_host;
            this.endpointMode = endpoint_mode ?? 'saas';
        } catch (err) {
            debug(`VROOM : Error fetching admin vroom settings: ${err.message}`);
        }
    }

    @task *saveVroomSettings() {
        try {
            yield this.fetch.post(
                'admin-settings',
                {
                    api_key: this.apiKey,
                    api_host: this.apiHost,
                    endpoint_mode: this.endpointMode,
                },
                { namespace: 'vroom/int/v1' }
            );
            this.notifications.success('VROOM settings saved.');
        } catch (err) {
            debug(`VROOM : Error saving admin vroom settings: ${err.message}`);
            this.notifications.serverError(err);
        }
    }
}
