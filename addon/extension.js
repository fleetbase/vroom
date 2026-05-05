import { ExtensionComponent } from '@fleetbase/ember-core/contracts';

export default {
    setupExtension(app, universe) {
        // Register VROOM Route Optimization
        universe.whenEngineLoaded('@fleetbase/fleetops-engine', this.registerVroom);

        // Register settings components
        universe.registerRenderableComponent('fleet-ops:template:settings:routing', new ExtensionComponent('@fleetbase/vroom-engine', 'organization/vroom-settings'));
        universe.registerRenderableComponent('fleet-ops:component:admin:routing-settings', new ExtensionComponent('@fleetbase/vroom-engine', 'admin/vroom-settings'));
    },

    async registerVroom(fleetopsEngine, universe) {
        const vroomEngine = await universe.extensionManager.ensureEngineLoaded('@fleetbase/vroom-engine');
        const routeOptimization = fleetopsEngine.lookup('service:route-optimization');
        const vroom = vroomEngine.lookup('service:vroom');
        if (routeOptimization && vroom) {
            routeOptimization.register('vroom', vroom);
        }
    },
};
