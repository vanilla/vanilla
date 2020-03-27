/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import getStore from "@library/redux/getStore";
import WebFont from "webfontloader";
import { getMeta, assetUrl, siteUrl } from "@library/utility/appUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { THEME_CACHE_EVENT } from "@library/styles/styleUtils";
import { IThemeFont } from "@library/theming/themeReducer";

const defaultFontConfig: WebFont.Config = {
    google: {
        families: ["Open Sans:400,400italic,600,700"],
    },
};

let loaded = false;

document.addEventListener(THEME_CACHE_EVENT, () => {
    loaded = false;
});

export function loadThemeFonts() {
    if (loaded) {
        return;
    }
    loaded = true;
    const state = getStore().getState();
    const assets = state.theme.assets.data || {};
    const { fonts } = assets;

    const globalVars = globalVariables();
    // Check for forced google fonts from the variables.

    const makeFontConfigFromFontVar = (fonts: IThemeFont[]) => {
        const webFontConfig: WebFont.Config = {
            custom: {
                families: fonts.map(font => font.name),
                urls: fonts.map(font => {
                    const url = new URL(siteUrl(assetUrl(font.url)));
                    url.searchParams.append("v", getMeta("context.cacheBuster"));
                    return url.href;
                }),
            },
        };
        return webFontConfig;
    };

    if (globalVars.fonts.customFontUrl) {
        const [firstFamily, ...restFamilies] = globalVars.fonts.families.body;
        WebFont.load(
            makeFontConfigFromFontVar([
                { name: firstFamily, url: globalVars.fonts.customFontUrl, fallbacks: restFamilies },
            ]),
        );
    } else if (globalVars.fonts.forceGoogleFont) {
        const firstFont = globalVars.fonts.families[0];
        const webFontConfig: WebFont.Config = {
            google: {
                families: [`${firstFont}:400,400italic,600,700`],
            },
        };
        WebFont.load(webFontConfig);
    } else if (fonts && fonts.data.length > 0) {
        const webFontConfig = makeFontConfigFromFontVar(fonts.data);

        if (webFontConfig.custom && webFontConfig.custom.urls && webFontConfig.custom.urls.length > 0) {
            WebFont.load(webFontConfig);
        }
    } else {
        // If the theme has no font config of its own, load the default.
        WebFont.load(defaultFontConfig);
    }
}
