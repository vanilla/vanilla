/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import getStore from "@library/redux/getStore";
import WebFont from "webfontloader";

const defaultFontConfig: WebFont.Config = {
    google: {
        families: ["Open Sans:400,400italic,600,700"],
    },
};

export function loadThemeFonts() {
    const state = getStore().getState();
    const assets = state.theme.assets.data || {};
    const { fonts } = assets;

    if (fonts && fonts.data.length > 0) {
        const webFontConfig: WebFont.Config = {
            custom: {
                families: fonts.data.map(font => font.name),
                urls: fonts.data.map(font => font.url),
            },
        };

        if (webFontConfig.custom && webFontConfig.custom.urls && webFontConfig.custom.urls.length > 0) {
            WebFont.load(webFontConfig);
        }
    } else {
        // If the theme has no font config of its own, load the default.
        WebFont.load(defaultFontConfig);
    }
}
