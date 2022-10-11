/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { searchBarVariables } from "@library/features/search/SearchBar.variables";
import { ButtonPreset } from "@library/forms/ButtonPreset";
import { IButton } from "@library/forms/styleHelperButtonInterface";
import { compactSearchVariables } from "@library/headers/mebox/pieces/compactSearchStyles";
import { containerVariables } from "@library/layout/components/containerStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ensureColorHelper } from "@library/styles/styleHelpers";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { IThemeVariables } from "@library/theming/themeReducer";
import { rgba, ColorHelper } from "csx";
import { breakpointVariables } from "@library/styles/styleHelpersBreakpoints";
import { t } from "@vanilla/i18n";
import { getMeta } from "@library/utility/appUtils";
import { Variables } from "@library/styles/Variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { SearchBarPresets } from "./SearchBarPresets";
import { Property } from "csstype";
import { inputVariables } from "@library/forms/inputStyles";
import { LocalVariableMapping } from "@library/styles/VariableMapping";

export enum BannerAlignment {
    LEFT = "left",
    CENTER = "center",
}

export type SearchPlacement = "middle" | "bottom";

export interface IBannerOptions {
    enabled: boolean;
    alignment: BannerAlignment;
    mobileAlignment: BannerAlignment;
    hideDescription: boolean;
    hideTitle: boolean;
    hideSearch: boolean;
    hideSearchOnMobile: boolean;
    hideIcon: boolean;
    searchPlacement: SearchPlacement;
    overlayTitleBar: boolean;
    url: string;
    deduplicateTitles: boolean;
    bgColor: ColorHelper;
    fgColor: ColorHelper;
    useOverlay?: boolean;
}

/**
 * @varGroup banner
 * @commonTitle Banner
 * @description The banner is a common component made up a background image and various pieces of configurable content.
 * Defaults include a title, description, and a searchbar.
 */
export const bannerVariables = useThemeCache(
    (optionOverrides?: Partial<IBannerOptions>, forcedVars?: IThemeVariables, altName?: string) => {
        const makeThemeVars = variableFactory(
            altName ?? "banner",
            forcedVars,
            new LocalVariableMapping({
                [`padding`]: "spacing.padding",
            }),
            !!altName,
        );
        const globalVars = globalVariables(forcedVars);
        const compactSearchVars = compactSearchVariables(forcedVars);
        const searchBarVars = searchBarVariables(forcedVars);

        const backgroundsInit = makeThemeVars("backgrounds", {
            /**
             * @var banner.backgrounds.useOverlay
             * @title Background Overlay
             * @description Apply an overlay color of the background for improved contrast.
             * This color is detected automatically, but can be overridden with the
             * banner.backgrounds.overlayColor variable.
             * @type boolean
             */
            useOverlay: true,

            /**
             * @var banner.backgrounds.overlayColor
             * @title Background Overlay Color
             * @description Choose a specific overlay color to go with banner.backgrounds.useOverlay.
             * @type string
             * @format hex-color
             */
            overlayColor: compactSearchVars.backgrounds.overlayColor,
        });

        /**
         * @varGroup banner.options
         * @commonTitle Options
         * @description Control different variants for the banner. These options can affect multiple parts of the banner at once.
         */
        const options = makeThemeVars(
            "options",
            {
                /**
                 * @var banner.options.enabled
                 * @title Enabled
                 * @description When disabled the banner will not appear at all.
                 * @type boolean
                 */
                enabled: true,

                /**
                 * @var banner.options.alignment
                 * @title Alignment
                 * @description Align the banner
                 * @type string
                 * @enum center | left | right
                 */
                alignment: BannerAlignment.CENTER,

                /**
                 * @var banner.options.mobileAlignment
                 * @title Alignment (Mobile)
                 * @description Align the banner on mobile. Defaults to match desktop alignment.
                 * @type string
                 * @enum center | left | right
                 */
                mobileAlignment: BannerAlignment.CENTER,

                /**
                 * @var banner.options.hideDescription
                 * @type boolean
                 */
                hideDescription: false,

                /**
                 * @var banner.options.hideTitle
                 * @type boolean
                 */
                hideTitle: false,

                /**
                 * @var banner.options.hideSearch
                 * @title Hide SearchBar
                 * @type boolean
                 */
                hideSearch: false,

                /**
                 * @var banner.options.hideSearchOnMobile
                 * @title Hide SearchBar on mobile views.
                 * @type boolean
                 */
                hideSearchOnMobile: false,

                /**
                 * @var banner.options.hideIcon
                 * @title Hide Icon
                 * @description Hide icon in banner. Defaults to true.
                 * @type boolean
                 */
                hideIcon: true,

                /**
                 * @var banner.options.searchPlacement
                 * @title SearchBar Placement
                 * @description Place the search bar in different parts of the banner.
                 * @type string
                 * @enum middle | bottom
                 */
                searchPlacement: "middle" as SearchPlacement,
                overlayTitleBar: true,

                /**
                 * @var banner.options.url
                 * @title Title Url
                 * @description When set turn the title into a link to this url.
                 * @type string
                 */
                url: "" as string,

                // Not publicly documented yet. Currently just an escape hatch in case we have issues on deployment.
                deduplicateTitles: true,
                bgColor: globalVars.mainColors.primary,
                fgColor: globalVars.mainColors.primaryContrast,
                useOverlay: backgroundsInit.useOverlay,
            } as IBannerOptions,
            optionOverrides,
        );

        // Main colors
        const colorsInit = makeThemeVars(
            "colors",
            {
                primary: options.bgColor,
                primaryContrast: options.fgColor,
                bg: options.bgColor,
            },
            optionOverrides?.fgColor && { primaryContrast: optionOverrides?.fgColor }, //this is to override variables if there are
        );

        const colors = makeThemeVars(
            "colors",
            {
                ...colorsInit,
                fg: colorsInit.primaryContrast,
            },
            optionOverrides?.fgColor && { primaryContrast: optionOverrides?.fgColor }, //this is to override variables if there are
        );

        const backgrounds = {
            ...backgroundsInit,
            ...(options.useOverlay != null && {
                useOverlay: options.useOverlay, //ensure options.useOverlay overrides backgrounds.useOverlay
                overlayColor: ColorsUtils.isLightColor(colors.fg)
                    ? globalVars.elementaryColors.black.fade(0.25)
                    : globalVars.elementaryColors.white.fade(0.25),
            }),
        };

        const presetsInit = makeThemeVars("presets", {
            input: {
                /**
                 * @var banner.presets.input.preset
                 * @title Input Preset
                 * @description Choose the type of input to use in the banner.
                 * @type string
                 * @enum no border | border | unified border
                 */
                preset: SearchBarPresets.NO_BORDER,
            },
        });

        const presets = makeThemeVars("presets", {
            ...presetsInit,
            button: {
                /**
                 * @var banner.presets.button.preset
                 * @title Button Preset
                 * @description Choose the type of button to apply to the banner.
                 * @type string
                 * @enum transparent | solid | hide
                 */
                preset:
                    presetsInit.input.preset === SearchBarPresets.UNIFIED_BORDER || // Unified border currently only supports solid buttons.
                    ColorsUtils.isDarkColor(colors.primaryContrast)
                        ? ButtonPreset.SOLID
                        : ButtonPreset.TRANSPARENT,
            },
        });

        const isSolidButton = presets.button.preset === ButtonPreset.SOLID;
        const isBordered = presets.input.preset === SearchBarPresets.BORDER;
        const isTransparentButton = presets.button.preset === ButtonPreset.TRANSPARENT;
        const isSolidBordered = isBordered && isSolidButton;

        /**
         * @varGroup banner.padding
         * @commonTitle Padding
         * @expand spacing
         */
        const padding = makeThemeVars(
            "padding",
            Variables.spacing({
                top: globalVars.spacer.pageComponent * 1.5,
                bottom: globalVars.spacer.pageComponent,
                horizontal: globalVars.gutter.half,
            }),
        );

        const dimensions = makeThemeVars("dimensions", {
            /**
             * @var banner.dimensions.minHeight
             * @title Minimum Height
             * @description Apply a minimum height. If the contents of the banner are less than this height,
             * they will be centered within it.
             */
            minHeight: 50,
            maxHeight: undefined,
            mobile: {
                /**
                 * @var banner.dimensions.minHeight
                 * @title Minimum Height (Mobile)
                 * @description Apply a minimum height on smaller device sizes. If the contents of the banner are less than
                 * this height, they will be centered within it.
                 */
                minHeight: undefined as undefined | number | string,
                maxHeight: undefined,
            },
        });

        const border = makeThemeVars("border", {
            /**
             * @var banner.border.width
             * @title Border Width
             * @description Choose the width of the banner border.
             * @type number|string
             */
            width: searchBarVars.border.width,

            /**
             * @var banner.border.width
             * @title Border Radius
             * @description Choose the radius of the banner border.
             * @type number|string
             */
            radius: searchBarVars.border.radius,
        });

        const contentContainer = makeThemeVars("contentContainer", {
            minWidth: 550,
            padding: Variables.spacing({
                top: padding.top,
                bottom: padding.bottom,
                horizontal: 0,
            }),
            mobile: {
                padding: Variables.spacing({
                    top: globalVars.spacer.componentInner * 2,
                    bottom: globalVars.spacer.componentInner,
                }),
            },
        });

        const rightImage = makeThemeVars("rightImage", {
            image: undefined as string | undefined,
            minWidth: 500,
            padding: Variables.spacing({
                vertical: globalVars.gutter.size,
                horizontal: containerVariables().spacing.padding * 2,
            }),
        });

        const logo = makeThemeVars("logo", {
            height: "auto" as number | string,
            width: 300 as number | string,
            padding: Variables.spacing({
                all: 12,
            }),
            image: undefined as string | undefined,
            mobile: {
                height: undefined as number | string | undefined,
                width: undefined as number | string | undefined,
            },
        });

        /**
         * @varGroup banner.icon
         * @title Icon
         * @description The icon (of the current category, for example) appearing in the Content banner
         */
        const iconDefaultVars = {
            /**
             * @var banner.icon.width
             * @title Width
             * @description Choose the width of the icon
             * @type number|string
             */
            width: undefined as number | string | undefined,
            /**
             * @var banner.icon.height
             * @title Height
             * @description Choose the height of the icon
             * @type number|string
             */
            height: undefined as number | string | undefined,
            /**
             * @varGroup banner.icon.margins
             * @title Margins
             * @description Set the margins around the icon
             * @expand spacing
             */
            margins: Variables.spacing({}),
            /**
             * @var banner.icon.image
             * @title Image
             * @description The URL where the icon image is hosted
             * @type string
             */
            image: undefined as string | undefined,
            /**
             * @var banner.icon.borderRadius
             * @title Border Radius
             * @description Choose the border radius of the icon
             * @type number|string
             */
            borderRadius: undefined as number | string | undefined,
        };

        const iconInit = makeThemeVars("icon", {
            ...iconDefaultVars,
        });

        const icon = makeThemeVars("icon", {
            ...iconInit,
            /**
             * @varGroup banner.icon.mobile
             * @title Mobile
             * @description Icon on mobile. By default, uses the same values in the banner.icon variable group.
             */

            /**
             * @var banner.icon.mobile.width
             * @title Width
             * @description Choose the width of the icon
             * @type number|string
             */

            /**
             * @var banner.icon.mobile.height
             * @title Height
             * @description Choose the height of the icon
             * @type number|string
             */

            /**
             * @varGroup banner.icon.mobile.margins
             * @title Margins
             * @description Set the margins around the icon
             * @expand spacing
             */

            /**
             * @var banner.icon.mobile.image
             * @title Image
             * @description The URL where the icon image is hosted
             * @type string
             */

            /**
             * @var banner.icon.mobile.borderRadius
             * @title Border Radius
             * @description Choose the border radius of the icon
             * @type number|string
             */
            mobile: {
                ...iconInit,
            },
        });

        const outerBackgroundInit = makeThemeVars(
            "outerBackground",
            /**
             * @varGroup banner.outerBackground
             * @commonTitle Background
             * @expand background
             */
            Variables.background({
                color: ColorsUtils.modifyColorBasedOnLightness({
                    color: colors.primary,
                    weight: 0.05,
                    inverse: true,
                }),
                repeat: "no-repeat",
                position: "50% 50%",
                size: "cover",
            }),
        );

        const outerBackground = makeThemeVars("outerBackground", {
            ...outerBackgroundInit,
            ...breakpointVariables({
                /**
                 * @varGroup banner.outerBackground.breakpoints.tablet
                 * @title Background (Tablet)
                 * @expand background
                 */
                tablet: {
                    ...Variables.background({}),
                    breakpointUILabel: t("Tablet"),
                },
                /**
                 * @varGroup banner.outerBackground.breakpoints.mobile
                 * @title Background (Mobile)
                 * @expand background
                 */
                mobile: {
                    ...Variables.background({}),
                    breakpointUILabel: t("Mobile"),
                },
            }),
        });

        const innerBackground = makeThemeVars(
            "innerBackground",
            Variables.background({
                unsetBackground: true,
                size: "unset",
            }),
        );

        const text = makeThemeVars("text", {
            shadowMix: 1, // We want to get the most extreme lightness contrast with text color (i.e. black or white)
            innerShadowOpacity: 0.25,
            outerShadowOpacity: 0.75,
        });

        const font = makeThemeVars(
            "font",
            /**
             * @varGroup banner.font
             * @commonTitle Font
             * @expand font
             */
            Variables.font({
                color: colors.primaryContrast,
                align: options.alignment,
                shadow: `0 1px 1px ${ColorsUtils.colorOut(
                    ensureColorHelper(
                        ColorsUtils.modifyColorBasedOnLightness({
                            color: colors.primaryContrast,
                            weight: text.shadowMix,
                        }),
                    ).fade(text.innerShadowOpacity),
                )}, 0 1px 25px ${ColorsUtils.colorOut(
                    ensureColorHelper(
                        ColorsUtils.modifyColorBasedOnLightness({
                            color: colors.primaryContrast,
                            weight: text.shadowMix,
                        }),
                    ).fade(text.outerShadowOpacity),
                )}`,
            }),
        );

        /**
         * @varGroup banner.textAndSearchContainer
         * @title Text & Search Container
         * @description In cases when we want banner text width to be different from that of the search bar.
         */
        const textAndSearchContainer = makeThemeVars("textAndSearchContainer", {
            /**
             * @var banner.textAndSearchContainer.maxWidth
             * @title maxWidth
             * @type number|string
             */
            maxWidth: 705 as number | string | undefined,
        });

        /**
         * @varGroup banner.title
         * @title Title
         */
        const title = makeThemeVars("title", {
            /**
             * @varGroup banner.title.font
             * @expand font
             * @title Font
             */
            font: Variables.font({
                ...font,
                ...globalVars.fontSizeAndWeightVars("largeTitle", "semiBold"),
            }),
            /**
             * @varGroup banner.title.fontMobile
             * @expand font
             * @title Font (Mobile)
             */
            fontMobile: Variables.font({
                ...font,
                ...globalVars.fontSizeAndWeightVars("title"),
                weight: font.weight,
            }),
            /**
             * @varGroup banner.title.margins
             * @title Spacing
             * @expand spacing
             */
            margins: Variables.spacing({
                bottom: globalVars.spacer.headingItem,
            }),
            text: getMeta("ui.siteName", t("How can we help you?")),
        });

        /**
         * @varGroup banner.description
         * @title Description
         */
        const description = makeThemeVars("description", {
            text: undefined as string | undefined,
            /**
             * @varGroup banner.description.font
             * @title Font
             * @expand font
             */
            font: Variables.font({
                ...font,
                ...globalVars.fontSizeAndWeightVars("large"),
                weight: font.weight,
            }),
            /**
             * @varGroup banner.description.margins
             * @title Spacing
             * @expand spacing
             */
            margins: Variables.spacing({
                bottom: 12,
            }),
        });

        /**
         * @varGroup banner.searchBar
         * @title SearchBar
         */
        const searchBarInit = makeThemeVars("searchBar", {
            preset: presets.button.preset,
            input: searchBarVars.input,
            sizing: {
                /**
                 * @var banner.searchBar.sizing.maxWidth
                 * @title Max Width
                 * @description Maximum width for the banner searchbar.
                 */
                maxWidth: textAndSearchContainer.maxWidth,

                /**
                 * @var banner.searchBar.sizing.height
                 * @title Height
                 * @description Height of the banner searchbar.
                 */
                height: Math.max(40, inputVariables().sizing.height),
            },
            border: {
                color: isBordered ? colors.primary : colors.bg,
                radius: border.radius,
                width: border.radius,
            },
        });

        const searchBar = makeThemeVars("searchBar", {
            preset: searchBarInit.preset,
            border: searchBarInit.border,
            sizing: {
                ...searchBarInit.sizing,
                heightMinusBorder: searchBarInit.sizing.height - searchBarInit.border.width * 2,
            },
            font: Variables.font({
                ...globalVars.fontSizeAndWeightVars("large"),
                weight: font.weight,
                color: searchBarInit.input.fg,
            }),
            margin: Variables.spacing({
                top: 24,
            }),
            marginMobile: Variables.spacing({
                top: 16,
            }),
            shadow: {
                show: false,
                style: `0 1px 1px ${ColorsUtils.colorOut(
                    ensureColorHelper(
                        ColorsUtils.modifyColorBasedOnLightness({
                            color: colors.fg,
                            weight: text.shadowMix,
                        }),
                    ).fade(text.innerShadowOpacity),
                )}, 0 1px 25px ${ColorsUtils.colorOut(
                    ensureColorHelper(
                        ColorsUtils.modifyColorBasedOnLightness({
                            color: colors.fg,
                            weight: text.shadowMix,
                        }),
                    ).fade(text.outerShadowOpacity),
                )}`,
            },

            state: {
                border: {
                    color: isSolidButton ? colors.fg : colors.primaryContrast,
                },
            },
        });

        let buttonBorderColor = colors.bg;
        if (isTransparentButton || isSolidBordered) {
            buttonBorderColor =
                presets.input.preset === SearchBarPresets.NO_BORDER
                    ? searchBarVars.input.bg
                    : searchBarVars.border.color;
        }

        let searchButton: IButton = makeThemeVars("searchButton", {
            ...searchBarVars.submitButton,
            name: "searchButton",
            presetName: presets.button.preset,
            sizing: {
                ...searchBarVars.submitButton.sizing,
                minHeight: searchBar.sizing.height,
            },
            colors: {
                bg: isTransparentButton ? rgba(0, 0, 0, 0) : colors.bg,
                fg: colors.fg,
            },
            borders: {
                color: buttonBorderColor,
                radius: searchBar.border.radius,
                left: {
                    radius: 0,
                },
            },
            fonts: {
                ...globalVars.fontSizeAndWeightVars("large", "bold"),
            },
        });

        let buttonStateBgColor = searchButton.state?.colors?.bg;
        let buttonStateFgColor = searchButton.state?.colors?.fg;
        let buttonStateBorderColor = searchButton.state?.borders?.color;

        if (isTransparentButton) {
            buttonStateBgColor = ColorsUtils.isLightColor(outerBackground.color!)
                ? globalVars.elementaryColors.white.fade(0.3)
                : globalVars.elementaryColors.black.fade(0.3);

            buttonStateFgColor = colors.primaryContrast;
            buttonStateBorderColor = searchButton.borders?.color;
        }

        searchButton = makeThemeVars(
            "searchButton",
            Variables.button({
                ...searchButton,
                state: {
                    ...searchButton.state,
                    colors: {
                        bg: buttonStateBgColor,
                        fg: buttonStateFgColor,
                    },
                    borders: {
                        ...searchButton.borders,
                        color: buttonStateBorderColor,
                    },
                },
            }),
        );

        // Unified border loops around whole search component including search button
        const unifiedBorder = makeThemeVars("unifiedBorder", {
            width: searchBarVars.border.width * 2,
            color: searchButton.colors!.bg!,
        });

        const buttonShadow = makeThemeVars("shadow", {
            color: ensureColorHelper(
                ColorsUtils.modifyColorBasedOnLightness({ color: colors.primaryContrast, weight: text.shadowMix }),
            ).fade(0.05),
            full: `0 1px 15px ${ColorsUtils.colorOut(
                ensureColorHelper(
                    ColorsUtils.modifyColorBasedOnLightness({ color: colors.primaryContrast, weight: text.shadowMix }),
                ).fade(0.3),
            )}`,
            background: ensureColorHelper(
                ColorsUtils.modifyColorBasedOnLightness({
                    color: colors.primaryContrast,
                    weight: text.shadowMix,
                }),
            )
                .fade(0.1)
                .toString(),
        });

        /**
         * @varGroup banner.searchStrip
         * @title Search Strip
         */
        const searchStrip = makeThemeVars("searchStrip", {
            /**
             * @var banner.searchStrip.bg
             * @title Background
             * @description Background for the search strip that appears with banner.options.searchPlacement = "bottom".
             * @type string
             * @format hex-color
             */
            bg: colors.primary as ColorHelper | undefined | string,

            /**
             * @var banner.searchStrip.minHeight
             * @title Minimum Height
             * @description Minimum height for the search strip that appears with banner.options.searchPlacement = "bottom".
             */
            minHeight: 60 as number | string,
            offset: undefined as number | string | undefined,
            padding: Variables.spacing({
                top: 12,
                bottom: 12,
            }),
            mobile: {
                bg: undefined as Property.BackgroundColor | undefined,
                minHeight: undefined as "string" | number | undefined,
                offset: undefined as "string" | number | undefined,
                padding: Variables.spacing({
                    bottom: undefined, // FIXME: this is just here so that overrides from storybook work
                }),
            },
        });

        return {
            font,
            presets,
            options,
            outerBackground,
            backgrounds,
            padding,
            innerBackground,
            contentContainer,
            dimensions,
            unifiedBorder,
            text,
            title,
            description,
            searchBar,
            buttonShadow,
            searchButton,
            colors,
            rightImage,
            border,
            searchStrip,
            logo,
            icon,
            textAndSearchContainer,
        };
    },
);
