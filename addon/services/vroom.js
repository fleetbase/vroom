import RouteOptimizationInterfaceService from '@fleetbase/fleetops-engine/services/route-optimization-interface';
import polyline from '@fleetbase/ember-core/utils/polyline';
import { inject as service } from '@ember/service';
import { debug } from '@ember/debug';
import { all } from 'rsvp';

export default class VroomService extends RouteOptimizationInterfaceService {
    name = 'VROOM';

    @service modalsManager;

    async optimize({ order, waypoints, context }, options = {}) {
        try {
            const task = await this.#prepareTask({ context, order, waypoints });
            const result = await this.solve(task, options);

            // handle processing for route result
            const route = result.routes[0];
            const geometry = polyline.decode(route.geometry);

            // Sort waypoints in returned order with mapping by ID
            const sortedWaypoints = route.steps
                .map((step) => {
                    if (step.type !== 'job') return null;
                    return waypoints[step.id - 1];
                })
                .filter(Boolean);

            return { sortedWaypoints, trip: { ...route, summary: result.summary }, route: geometry, result, engine: 'vroom' };
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
                task.vehicles = [await this.#createVehicle(order.driver_assigned)];
                task.jobs = await all(waypoints.map((wp, idx) => this.#createJob(wp, idx + 1)));
                break;

            default:
                break;
        }

        return task;
    }

    async #createVehicle(driverOrVehicle, id = 1) {
        // If `driverOrVehicle.vehicle` exists, await it; otherwise treat
        // `driverOrVehicle` as the vehicle itself.
        const maybeVehicle = driverOrVehicle.vehicle ? await driverOrVehicle.vehicle : driverOrVehicle;

        // If the relationship resolved to null/undefined, fall back to the original
        // object. Otherwise use the resolved vehicle.
        const vehicle = maybeVehicle ?? driverOrVehicle;

        // Create vroom compatible instance
        return {
            id,
            start: vehicle.get('location.coordinates'),
        };
    }

    async #createJob(waypoint, id = 1) {
        return {
            id,
            location: waypoint.get('place.location.coordinates'),
        };
    }
}
