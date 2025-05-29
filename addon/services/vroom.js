import RouteOptimizationInterfaceService from '@fleetbase/fleetops-engine/services/route-optimization-interface';
import { inject as service } from '@ember/service';
import { debug } from '@ember/debug';
import { all } from 'rsvp';

export default class VroomService extends RouteOptimizationInterfaceService {
    name = 'VROOM';

    @service modalsManager;

    async optimize(params, options = {}) {
        try {
            const task = await this.#prepareTask(params);
            console.log('[task]', task, options);
            const result = await this.solve(task);

            // do some processing and send back a optimized route
            return result;
        } catch (err) {
            debug(`[VROOM] Error solving route optimization task : ${err.message}`);
            throw err;
        }
    }

    solve({ vehicles = [], jobs = [], shipments = [] }, options = {}) {
        return this.#request('solve', { vehicles, jobs, shipments }, options);
    }

    plan(data, options = {}) {
        return this.#request('plan', data, options);
    }

    #request(path, data = {}, options = {}) {
        return this.fetch.post(path, data, { namespace: 'vroom/int/v1', ...options });
    }

    async #prepareTask({ context, order, waypoints }) {
        const task = {};

        switch (context) {
            case 'create_order':
                console.log('[waypoints]', waypoints);
                task.vehicles = [await this.#createVehicle(order.driver_assigned)];
                task.jobs = await all(waypoints.map((wp, idx) => this.#createJob(wp, idx + 1)));
                break;

            default:
                break;
        }

        return task;
    }

    async #createVehicle(driverOrVehicle, id = 1) {
        // Vehicle is a prop then its a driver and we should use their assigned vehicle
        let vehicle;
        if (driverOrVehicle.vehicle) {
            vehicle = await driverOrVehicle.vehicle;
        } else {
            vehicle = driverOrVehicle;
        }

        console.log('[vehicle]', vehicle);

        // Create vroom compatible instance
        return {
            id,
            start: vehicle.get('coordinates'),
        };
    }

    async #createJob(waypoint, id = 1) {
        console.log('[waypoint]', waypoint);
        return {
            id,
            location: waypoint.get('place.coordinates'),
        };
    }
}
