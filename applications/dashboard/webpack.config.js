const path = require("path");
const TerserPlugin = require("terser-webpack-plugin");

module.exports = {
    mode: "production",
    entry: {
        dashboard: [
            "./js/src/lithe.js",
            "./js/src/lithe.drawer.js",
            "./js/src/modal.dashboard.js",
            "./js/spoiler.js",
            "./js/src/main.js",
        ],
    },
    output: {
        path: path.resolve(__dirname, "js"),
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                use: ["babel-loader"],
            },
        ],
    },
    optimization: {
        minimize: true,
        minimizer: [
            new TerserPlugin({
                extractComments: false,
                terserOptions: {
                    compress: false,
                    mangle: false,
                    format: {
                        comments: /@license/i,
                    },
                },
            }),
        ],
    },
};
