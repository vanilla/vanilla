/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

export function svgLoader() {
    return {
        test: /\.svg$/,
        use: [
            {
                loader: "babel-loader",
                options: {
                    presets: [require.resolve("@vanilla/babel-preset")],
                    cacheDirectory: true,
                },
            },
            {
                loader: "@svgr/webpack",
                options: {
                    babel: false,
                    replaceAttrValues: {
                        "#000": "currentColor",
                        "#555A62": "currentColor",
                    },
                    svgoConfig: {
                        plugins: [
                            {
                                // Prevent stripping of viewBoxes.
                                // When the viewBox is stripped, it is not possible
                                // To scale an SVG with CSS height/size.
                                removeViewBox: false,
                            },
                        ],
                    },
                },
            },
        ],
    };
}
