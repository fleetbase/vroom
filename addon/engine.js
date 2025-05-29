import Engine from '@ember/engine';
import loadInitializers from 'ember-load-initializers';
import Resolver from 'ember-resolver';
import config from './config/environment';
import services from '@fleetbase/ember-core/exports/services';

const { modulePrefix } = config;
const externalRoutes = ['console', 'extensions'];
const FLEETOPS_ENGINE_NAME = '@fleetbase/fleetops-engine';

export default class VroomEngine extends Engine {
    modulePrefix = modulePrefix;
    Resolver = Resolver;
    dependencies = {
        services,
        externalRoutes,
    };
    engineDependencies = [FLEETOPS_ENGINE_NAME];
    /* eslint no-unused-vars: "off" */
    setupExtension = function (app, engine, universe) {
        const routeOptimization = app.lookup('service:route-optimization');
        const vroom = app.lookup('service:vroom');
        if (routeOptimization && vroom) {
            routeOptimization.register('vroom', vroom);
        }
    };
}

loadInitializers(VroomEngine, modulePrefix);
