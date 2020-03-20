/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { bannerVariables, bannerClasses, BannerAlignment } from "@library/banner/bannerStyles";
import clamp from "lodash/clamp";

export const CONTENT_BANNER_MAX_HEIGHT = 180;
export const CONTENT_BANNER_MIN_HEIGHT = 80;

export const contentBannerVariables = useThemeCache(() => {
    const makeVars = variableFactory("contentBanner");

    const dimensions = makeVars("dimensions", {
        minHeight: 120,
        mobile: {
            minHeight: 80,
        },
    });

    const minHeight = clamp(dimensions.minHeight, CONTENT_BANNER_MIN_HEIGHT, CONTENT_BANNER_MAX_HEIGHT);
    const minHeightMobile = clamp(dimensions.mobile.minHeight, CONTENT_BANNER_MIN_HEIGHT, CONTENT_BANNER_MAX_HEIGHT);

    const options = makeVars("options", {
        enabled: false,
        alignment: BannerAlignment.CENTER,
        mobileAlignment: BannerAlignment.LEFT,
    });

    const normalBannerVars = bannerVariables();

    return bannerVariables(
        {
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
                    height: minHeight - normalBannerVars.logo.padding.all * 2,
                    width: "auto",
                    mobile: {
                        height: minHeightMobile - normalBannerVars.logo.padding.all * 2,
                    },
                },
                spacing: {
                    padding: {
                        top: 0,
                        bottom: 0,
                    },
                },
                contentContainer: {
                    mobile: {
                        padding: {
                            top: 0,
                            bottom: 0,
                        },
                    },
                },
            },
        },
        "contentBanner",
    );
});

export const contentBannerClasses = useThemeCache(() => {
    return bannerClasses(contentBannerVariables());
});
