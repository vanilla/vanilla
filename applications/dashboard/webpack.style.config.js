const path = require("path");
var glob = require("globby");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const IgnoreEmitPlugin = require("ignore-emit-webpack-plugin");

module.exports = (env, options) => {
    const entry = Object.fromEntries(
        glob.sync("./scss/**/*.scss").reduce((entries, path) => {
            const name = path.split("/").slice(-1)[0];
            if (name[0] !== "_" && !path.includes("/vendors/")) {
                return [...entries, [name.replace(".scss", ""), path]];
            }
            return entries;
        }, ""),
    );

    return {
        mode: "production",
        entry,
        output: {
            path: path.resolve(__dirname, "design"),
        },
        devtool: "source-map",
        module: {
            rules: [
                {
                    test: /\.scss$/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        { loader: "css-loader", options: { url: false, sourceMap: true } },
                        {
                            loader: "postcss-loader",
                            options: {
                                postcssOptions: {
                                    plugins: [
                                        [
                                            "autoprefixer",
                                            {
                                                overrideBrowserslist: [
                                                    "Edge >= 83",
                                                    "Firefox >= 78",
                                                    "FirefoxAndroid >= 78",
                                                    "Chrome >= 80",
                                                    "ChromeAndroid >= 80",
                                                    "Opera >= 67",
                                                    "OperaMobile >= 67",
                                                    "Safari >= 12",
                                                    "iOS >= 12",
                                                ],
                                            },
                                        ],
                                    ],
                                },
                            },
                        },
                        {
                            loader: "sass-loader",
                            options: {
                                sourceMap: true,
                                sassOptions: {
                                    outputStyle: "expanded",
                                    includePaths: [path.resolve(__dirname, "./scss/maps/")],
                                },
                            },
                        },
                    ],
                },
                {
                    test: /\.(png|svg|jpg|jpeg|gif)$/,
                    loader: "file-loader",
                },
                {
                    test: /\.(woff|woff2|eot|ttf|otf)$/,
                    loader: "file-loader",
                },
            ],
        },
        plugins: [
            new MiniCssExtractPlugin({
                filename: "[name].css",
            }),
            new IgnoreEmitPlugin(/.js$/),
        ],
    };
};
