/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { bannerVariables, bannerClasses, BannerAlignment } from "@library/banner/bannerStyles";
import clamp from "lodash/clamp";
import { IThemeVariables } from "@library/theming/themeReducer";
import merge from "lodash/merge";
import clone from "lodash/clone";
import { IMediaQueryFunction } from "@library/layout/types/interface.panelLayout";
import { Variables } from "@library/styles/Variables";

export const CONTENT_BANNER_MAX_HEIGHT = 180;
export const CONTENT_BANNER_MIN_HEIGHT = 80;

export const contentBannerVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeVars = variableFactory("contentBanner", forcedVars);

    const dimensions = makeVars("dimensions", {
        minHeight: 120,
        mobile: {
            minHeight: 120,
        },
    });

    const minHeight = clamp(dimensions.minHeight, CONTENT_BANNER_MIN_HEIGHT, CONTENT_BANNER_MAX_HEIGHT);
    const minHeightMobile = clamp(dimensions.mobile.minHeight, CONTENT_BANNER_MIN_HEIGHT, CONTENT_BANNER_MAX_HEIGHT);

    const options = makeVars("options", {
        enabled: false,
        alignment: BannerAlignment.CENTER,
        mobileAlignment: BannerAlignment.LEFT,
        overlayTitleBar: false,
    });

    const contentContainer = makeVars("contentContainer", {
        padding: Variables.spacing({}),
        mobile: {
            padding: Variables.spacing({
                top: 0,
                bottom: 0,
            }),
        },
    });

    const normalBannerVars = bannerVariables(forcedVars, "contentBanner");

    const forced = merge(clone(forcedVars), {
        contentBanner: {
            options: {
                ...options,
                hideDescription: true,
                hideTitle: true,
                hideSearch: true,
            },
            dimensions: {
                minHeight: minHeight,
                mobile: {
                    minHeight: minHeightMobile,
                },
            },
            logo: {
                height: minHeight - (normalBannerVars.logo.padding.all as number) * 2,
                width: "auto",
                mobile: {
                    height: minHeightMobile - (normalBannerVars.logo.padding.all as number) * 2,
                },
            },
            spacing: {
                padding: {
                    top: 0,
                    bottom: 0,
                },
            },
            contentContainer,
        },
    });
    return bannerVariables(forced, "contentBanner");
});

export const contentBannerClasses = useThemeCache(
    (mediaQueries: IMediaQueryFunction, options?: { debug?: boolean | string }) => {
        return bannerClasses(mediaQueries, contentBannerVariables(), "contentBanner", options);
    },
);
