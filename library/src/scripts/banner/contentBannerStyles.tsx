/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { bannerVariables, BannerAlignment, IBannerOptions } from "@library/banner/Banner.variables";
import { bannerClasses } from "@library/banner/Banner.styles";
import clamp from "lodash/clamp";
import { IThemeVariables } from "@library/theming/themeReducer";
import merge from "lodash/merge";
import clone from "lodash/clone";
import { IMediaQueryFunction } from "@library/layout/types/interface.panelLayout";
import { Variables } from "@library/styles/Variables";
import { cx, css } from "@emotion/css";
import { styleUnit } from "@library/styles/styleUnit";

export const CONTENT_BANNER_MAX_HEIGHT = 180;
export const CONTENT_BANNER_MIN_HEIGHT = 80;

export const contentBannerVariables = useThemeCache(
    (optionOverrides?: Partial<IBannerOptions>, forcedVars?: IThemeVariables) => {
        const makeVars = variableFactory("contentBanner", forcedVars);

        const normalBannerVars = bannerVariables(optionOverrides, forcedVars, "contentBanner");

        const options: IBannerOptions = makeVars("options", {
            ...normalBannerVars.options,
            enabled: false,
            alignment: BannerAlignment.CENTER,
            mobileAlignment: BannerAlignment.LEFT,
            overlayTitleBar: false,
            hideDescription: true,
            hideTitle: true,
            hideSearch: true,
            hideIcon: true,
        });

        const dimensions = makeVars("dimensions", {
            minHeight: 120,
            mobile: {
                minHeight: 120,
            },
        });

        const minHeight = clamp(dimensions.minHeight, CONTENT_BANNER_MIN_HEIGHT, CONTENT_BANNER_MAX_HEIGHT);
        const minHeightMobile = clamp(
            dimensions.mobile.minHeight,
            CONTENT_BANNER_MIN_HEIGHT,
            CONTENT_BANNER_MAX_HEIGHT,
        );

        const icon = makeVars("icon", {
            image: undefined,
            width: 100,
            height: 100,
            borderRadius: "100%",
            margins: Variables.spacing({
                top: normalBannerVars.title.margins.top, //make the icon align with the title
                right: 20,
            }),
            mobile: {
                width: 80,
                height: 80,
            },
        });

        const spacing = makeVars("spacing", {
            padding: Variables.spacing({}),
            mobile: {
                padding: Variables.spacing({
                    top: 0,
                    bottom: 0,
                }),
            },
        });

        const forced = merge(clone(forcedVars), {
            contentBanner: {
                options,
                dimensions,
                spacing,
                logo: {
                    height: minHeight - (normalBannerVars.logo.padding.all as number) * 2,
                    width: "auto",
                    mobile: {
                        height: minHeightMobile - (normalBannerVars.logo.padding.all as number) * 2,
                    },
                },
                icon,
            },
        });
        return bannerVariables(optionOverrides, forced, "contentBanner");
    },
);

export const contentBannerClasses = useThemeCache((optionOverrides?: Partial<IBannerOptions>) => {
    const vars = contentBannerVariables(optionOverrides);
    const classes = bannerClasses(vars, optionOverrides);
    return {
        ...classes,
        textAndSearchContainer: cx(
            classes.textAndSearchContainer,
            css({
                flexBasis: styleUnit(vars.searchBar.sizing.maxWidth),
                flexGrow: 1,
            }),
        ),
    };
});
