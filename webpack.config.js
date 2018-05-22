/**
 * This is here for PHPStorm path resolving only.
 */

const path = require("path");

module.exports = {
    resolve: {
        alias: {
            "@dashboard": path.resolve(__dirname, "src/scripts/"),
            "@dashboard": path.resolve(__dirname, "applications/dashboard/src/scripts"),
            "@vanilla": path.resolve(__dirname, "applications/vanilla/src/scripts"),
        },
    },
};
