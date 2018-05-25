/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

const path = require("path");
const webpackConfig = require("./webpack.test.config");
const VANILLA_ROOT = path.resolve(path.join(__dirname, "../../"));

module.exports = function(config) {
    config.set({
        // base path, that will be used to resolve files and exclude
        basePath: VANILLA_ROOT,
        frameworks: ["mocha", "chai"],
        files: [
            "applications/*/src/scripts/__tests__/**/*.test.ts",
            "plugins/*/src/scripts/__tests__/**/*.test.ts",
        ],
        preprocessors: {
            "applications/*/src/scripts/__tests__/**/*.test.ts": ["webpack", "sourcemap"],
            "plugins/*/src/scripts/__tests__/**/*.test.ts": ["webpack", "sourcemap"],
        },
        reporters: ['mocha'],
        logLevel: config.LOG_INFO,
        port: 9876, // karma web server port
        colors: true,
        mime: {
            'text/x-typescript':  ['ts']
        },
        browsers: ["Chrome"],
        autoWatch: true,
        webpackMiddleware: {
            // webpack-dev-middleware configuration
            // i. e.
            stats: "errors-only"
        },
        webpack: webpackConfig,
        singleRun: false, // Karma captures browsers, runs the tests and exits
        concurrency: Infinity,
    });
};
