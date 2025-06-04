import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { debug } from '@ember/debug';
import { task } from 'ember-concurrency';

export default class VroomSettingsComponent extends Component {
    @service fetch;
    @service notifications;
    @tracked apiKey;

    constructor(owner, { controller }) {
        super(...arguments);
        controller.registerSaveTask(this.saveVroomSettings);
        this.loadVroomSettings.perform();
    }

    @task *loadVroomSettings() {
        try {
            const { api_key } = yield this.fetch.get('settings', {}, { namespace: 'vroom/int/v1' });
            this.apiKey = api_key;
        } catch (err) {
            debug(`VROOM : Error fetching vroom settings: ${err.message}`);
        }
    }

    @task *saveVroomSettings() {
        try {
            yield this.fetch.post('settings', { api_key: this.apiKey }, { namespace: 'vroom/int/v1' });
            this.notifications.success('VROOM Settings saved.');
        } catch (err) {
            debug(`VROOM : Error saving vroom settings: ${err.message}`);
        }
    }
}
