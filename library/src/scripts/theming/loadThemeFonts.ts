/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import getStore from "@library/redux/getStore";
import WebFont from "webfontloader";
import { getMeta, assetUrl, siteUrl, isAllowedUrl } from "@library/utility/appUtils";
import { defaultFontFamily, globalVariables } from "@library/styles/globalStyleVars";
import { THEME_CACHE_EVENT } from "@library/styles/themeCache";
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

const getGoogleFontUrl = (
    props: {
        name: string;
        weightNormal?: string | number;
        weightSemiBold?: string | number;
        weightBold?: string | number;
    },
    prefix?: boolean,
) => {
    const globalVars = globalVariables();
    const {
        name = defaultFontFamily,
        weightNormal = globalVars.fonts.weights.normal,
        weightSemiBold = globalVars.fonts.weights.semiBold,
        weightBold = globalVars.fonts.weights.bold,
    } = props;
    return `${prefix ? "https://fonts.googleapis.com/css?family=" : ""}${encodeURI(
        name,
    )}:${weightNormal},${weightNormal}italic,${weightSemiBold},${weightBold}`;
};

const validCustomFont = (customFont?: IThemeFont) => {
    return customFont && customFont.url && customFont.url !== "" && customFont.name && customFont.name !== "";
};

const makeFontConfigFromFontVar = (fonts: IThemeFont[], isMonoSpace?: boolean) => {
    const filteredFonts = fonts.filter((font) => {
        return validCustomFont(font);
    });
    const webFontConfig: WebFont.Config = {
        custom: {
            families: filteredFonts.map((font) => font.name),
            urls: filteredFonts.map((font) => {
                return assetUrl(`${font.url}?v=${getMeta("context.cacheBuster")}`);
            }),
        },
    };
    return webFontConfig;
};

export function loadThemeFonts() {
    if (loaded) {
        return;
    }
    loaded = true;
    const state = getStore().getState();
    const assets = state.theme.assets.data || {};
    const { fonts = { data: [] } } = assets;

    const globalVars = globalVariables();
    const globalFontVars = globalVars.fonts;
    const forceGoogleFont = globalFontVars.forceGoogleFont;
    const customFont = globalFontVars.customFont;
    const defaultFallback = globalVars.fonts.families[0];
    const googleFontFamily = globalFontVars.googleFontFamily || defaultFallback;

    if (globalVars.fonts.customFontUrl) {
        // Legacy case, do not use globalVars.fonts.customFontUrl in the future
        const [firstFamily, ...restFamilies] = globalVars.fonts.families.body;
        WebFont.load(
            makeFontConfigFromFontVar([
                { name: firstFamily, url: globalVars.fonts.customFontUrl, fallbacks: restFamilies },
            ]),
        );
    } else if (!forceGoogleFont && validCustomFont(customFont as IThemeFont)) {
        const fontLoaderProps = [
            customFont as IThemeFont,
            ...[...customFont.fallbacks, defaultFontFamily].map((fontFamily) => {
                return {
                    name: fontFamily,
                    url: fontFamily === defaultFontFamily ? getGoogleFontUrl({ name: defaultFallback }, true) : "",
                };
            }),
        ].filter((font) => {
            return font && customFont.name && customFont.name !== "";
        });

        const customConfig = {
            families: fontLoaderProps.map((font) => font.name),
            urls: fontLoaderProps.map((font) => {
                return assetUrl(`${font.url}?v=${getMeta("context.cacheBuster")}`);
            }),
        };

        WebFont.load({
            custom: customConfig,
        });
    } else if (forceGoogleFont) {
        const webFontConfig: WebFont.Config = {
            google: {
                families: [getGoogleFontUrl({ name: googleFontFamily })],
            },
        };
        WebFont.load(webFontConfig);
    } else if (fonts && fonts.data && fonts.data.length > 0) {
        const webFontConfig = makeFontConfigFromFontVar(fonts.data);
        if (webFontConfig.custom && webFontConfig.custom.urls && webFontConfig.custom.urls.length > 0) {
            WebFont.load(webFontConfig);
        }
    } else {
        // If the theme has no font config of its own, load the default.
        WebFont.load(defaultFontConfig);
    }
}
