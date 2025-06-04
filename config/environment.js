'use strict';
const { name } = require('../package');

module.exports = function (environment) {
    let ENV = {
        modulePrefix: name,
        environment,

        vroom: {
            apiKey: getenv('VROOM_API_KEY'),
        },
    };

    return ENV;
};

function getenv(variable, defaultValue = null) {
    return process.env[variable] !== undefined ? process.env[variable] : defaultValue;
}
