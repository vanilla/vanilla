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
    console.log("webFontConfig: ", webFontConfig);
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
    // const forceGoogleFont = assets.variables?.data.global.fonts.forceGoogleFont;
    const globalFontVars = globalVariables().fonts;
    const forceGoogleFont = globalFontVars.forceGoogleFont;
    const customFont = globalFontVars.customFont;

    // console.log("fonts: ", fonts);
    // console.log("state: ", state);
    console.log("globals.fonts.customFont: ", globalVariables().fonts.customFont);

    // const customFont = fonts.unshift();
    // if (
    //     !forceGoogleFont &&
    //     customFont &&
    //     customFont.name &&
    //     customFont.name != "" &&
    //     customFont.url &&
    //     isAllowedUrl(customFont.url)
    // ) {
    //     fonts.data.unshift(customFont);
    //     isCustomFont = true;
    // }

    // const families = assets.variables?.data.global.fonts.families.body;

    // const customFont: IThemeFont = assets.variables?.data.global.fonts.customFont;

    if (!forceGoogleFont && validCustomFont(customFont as IThemeFont)) {
        //const [firstFamily, ...restFamilies] = fonts;
        // const props = { ...fonts.data } as IThemeFont;
        console.log("customFont - props: ", customFont);
        WebFont.load(
            makeFontConfigFromFontVar([
                customFont as IThemeFont,
                {
                    name: defaultFontFamily,
                    url: getGoogleFontUrl({ name: defaultFontFamily }, true),
                },
                ...fontFallbacks.map(fontFamily => {
                    return {
                        name: fontFamily,
                        url: "",
                    };
                }),
            ]),
        );
    } else if (forceGoogleFont) {
        console.log("2. forceGoogleFont", forceGoogleFont);
        const firstFont = globalVariables().fonts.families[0];
        const webFontConfig: WebFont.Config = {
            google: {
                // families: [`"${assets.variables?.data.global.fonts.firstFont}":400,400italic,600,700`],
                families: [getGoogleFontUrl(firstFont)],
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
