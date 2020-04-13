/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import getStore from "@library/redux/getStore";
import WebFont from "webfontloader";
import { getMeta, assetUrl, siteUrl, isAllowedUrl } from "@library/utility/appUtils";
import { defaultFontFamily, globalVariables } from "@library/styles/globalStyleVars";
import { THEME_CACHE_EVENT } from "@library/styles/styleUtils";
import { IThemeFont } from "@library/theming/themeReducer";
import { fontFallbacks, monoFallbacks } from "@library/styles/styleHelpersTypography";

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
    )}:${weightNormal},${weightNormal}italic,${weightSemiBold},${weightSemiBold}`;
};

const validCustomFont = (customFont?: IThemeFont) => {
    return customFont && customFont.url && customFont.url !== "" && customFont.name && customFont.name !== "";
};

const makeFontConfigFromFontVar = (fonts: IThemeFont[], isMonoSpace?: boolean) => {
    const filteredFonts = fonts.filter(font => {
        return validCustomFont(font);
    });
    const webFontConfig: WebFont.Config = {
        custom: {
            families: filteredFonts.map(font => font.name),
            urls: filteredFonts.map(font => {
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

    const globalFontVars = globalVariables().fonts;
    const forceGoogleFont = globalFontVars.forceGoogleFont;
    const customFont = globalFontVars.customFont;

    const defaultFallback = globalVariables().fonts.families[0];

    if (!forceGoogleFont && validCustomFont(customFont as IThemeFont)) {
        //const [firstFamily, ...restFamilies] = fonts;
        // const props = { ...fonts.data } as IThemeFont;
        console.log("customFont - props: ", customFont);

        const fontLoaderProps = [
            customFont as IThemeFont,
            // {
            //     name: defaultFontFamily,
            //     url: getGoogleFontUrl({ name: defaultFontFamily }, true),
            // },
            ...[...customFont.fallbacks, defaultFontFamily, ...fontFallbacks].map(fontFamily => {
                return {
                    name: fontFamily,
                    url: fontFamily === defaultFontFamily ? getGoogleFontUrl({ name: defaultFontFamily }, true) : "",
                };
            }),
        ].filter(font => {
            return font && customFont.url && customFont.name && customFont.name !== "";
        });

        console.log("fontLoaderProps: ", fontLoaderProps);

        const mainFont = WebFont.load(makeFontConfigFromFontVar(fontLoaderProps));
    } else if (forceGoogleFont) {
        console.log("2. forceGoogleFont", forceGoogleFont);
        const webFontConfig: WebFont.Config = {
            google: {
                // families: [`"${assets.variables?.data.global.fonts.firstFont}":400,400italic,600,700`],
                families: [getGoogleFontUrl(defaultFallback)],
            },
        };
        WebFont.load(webFontConfig);
    } else if (fonts && fonts.data.length > 0) {
        console.log("3. fonts && fonts.data.length > 0", fonts);
        const webFontConfig = makeFontConfigFromFontVar(fonts.data);
        if (webFontConfig.custom && webFontConfig.custom.urls && webFontConfig.custom.urls.length > 0) {
            WebFont.load(webFontConfig);
        }
    } else {
        console.log("4. else: ");
        // If the theme has no font config of its own, load the default.
        WebFont.load(defaultFontConfig);
    }
}
