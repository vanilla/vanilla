/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import getStore from "@library/redux/getStore";
import WebFont from "webfontloader";
import { getMeta, assetUrl, siteUrl } from "@library/utility/appUtils";

const defaultFontConfig: WebFont.Config = {
    google: {
        families: ["Open Sans:400,400italic,600,700"],
    },
};

let loaded = false;

export function loadThemeFonts() {
    if (loaded) {
        return;
    }
    loaded = true;
    const state = getStore().getState();
    const assets = state.theme.assets.data || {};
    const { fonts } = assets;

    if (fonts && fonts.data.length > 0) {
        const webFontConfig: WebFont.Config = {
            custom: {
                families: fonts.data.map(font => font.name),
                urls: fonts.data.map(font => {
                    const url = new URL(siteUrl(assetUrl(font.url)));
                    url.searchParams.append("v", getMeta("context.cacheBuster"));
                    return url.href;
                }),
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
