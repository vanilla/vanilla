const path = require("path");

module.exports = {
    resolve: {
        alias: {
            "@core": path.resolve(__dirname, "src/scripts/"),
            "@dashboard": path.resolve(__dirname, "applications/dashboard/src/scripts"),
            "@vanilla": path.resolve(__dirname, "applications/vanilla/src/scripts"),
        },
    },
};
