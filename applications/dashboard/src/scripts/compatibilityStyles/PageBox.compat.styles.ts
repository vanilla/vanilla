/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { MixinsFoundation } from "@library/styles/MixinsFoundation";
import { useThemeCache } from "@library/styles/themeCache";
import { Mixins } from "@library/styles/Mixins";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { percent } from "csx/lib/units";
import { extendItemContainer } from "@library/styles/styleHelpers";
import { injectGlobal } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";

export const pageBoxCompatStyles = useThemeCache(() => {
    const globalVars = globalVariables();
    const mediaQueries = oneColumnVariables().mediaQueries();

    MixinsFoundation.contentBoxes(globalVars.contentBoxes);
    MixinsFoundation.contentBoxes(globalVars.panelBoxes, undefined, ".Panel");

    // Apply heading boxes

    const allHeadings =
        "& h1:not(.subtitle), & h2:not(.subtitle), & h3:not(.subtitle), & h4:not(.subtitle), & h5:not(.subtitle), & [role='heading']:not(.subtitle)";
    injectGlobal({
        ".pageHeadingBox.pageHeadingBox.pageHeadingBox": {
            margin: 0,
            padding: 0,
            display: "flex",
            alignItems: "center",
            flexWrap: "wrap",
            border: "none",
            justifyContent: "space-between",
            ...Mixins.margin({ bottom: globalVars.spacer.headingBox }),
            ...mediaQueries.oneColumnDown({
                ...Mixins.margin({ bottom: globalVars.spacer.headingBoxCompact }),
            }),
            ...mediaQueries.xs({
                minWidth: 150,
            }),

            "&:first-child": {
                paddingTop: 0,
            },

            ".PageTitle": {
                width: percent(100),
            },

            [allHeadings]: {
                padding: 0,

                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars("title"),
                    color: ColorsUtils.colorOut(globalVars.mainColors.fgHeading),
                }),

                width: "auto",
                flex: 1,
                justifyContent: "flex-start",
                ...Mixins.margin({ all: 0, bottom: globalVars.spacer.headingItem }),
                ...mediaQueries.oneColumnDown({
                    fontSize: globalVars.fonts.mobile.size.title,
                }),

                "& > a:not([role='button'])": {
                    fontSize: "inherit",
                    fontWeight: "inherit",
                    color: "inherit",
                },
            },

            "&.isLarge": {
                [allHeadings]: {
                    ...Mixins.font({
                        ...globalVars.fontSizeAndWeightVars("largeTitle"),
                    }),
                    ...mediaQueries.oneColumnDown({
                        fontSize: globalVars.fonts.mobile.size.largeTitle,
                    }),
                },
            },

            "& > p, & > .userContent, & > .P, & > .PageDescription": {
                width: "100%",
                padding: 0,
                ...Mixins.margin({ bottom: globalVars.spacer.headingItem }),
            },
        },
        ".MainContent .pageHeadingBox.pageHeadingBox.pageHeadingBox": {
            ...Mixins.margin({ bottom: globalVars.spacer.headingBoxCompact }),
        },
        ".pageBox .Empty": {
            margin: 0,
            border: "none",
        },
        ".pageBox + .PageControls": {
            marginTop: 16,
        },

        ".GuestBox": {
            margin: 0,
            marginBottom: 4,

            "& .GuestBox-buttons": {
                width: "100%",
                display: "flex",
                marginBottom: 4,
                ...extendItemContainer(4),
            },

            "& .Button": {
                flex: 1,
                margin: 4,
            },
        },
    });

    injectGlobal({
        ".Panel .pageHeadingBox.pageHeadingBox.pageHeadingBox.pageHeadingBox.pageHeadingBox": {
            padding: 0,
            // Super compact.
            ...Mixins.margin({ bottom: 0 }),
            [allHeadings]: {
                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars("subTitle"),
                }),
            },
            ...mediaQueries.oneColumnDown({
                ...Mixins.margin({ bottom: 0 }),
                [allHeadings]: {
                    ...Mixins.font({
                        ...globalVars.fontSizeAndWeightVars("subTitle"),
                    }),
                },
            }),
        },
    });

    injectGlobal({
        ".headerBoxLayout": {
            position: "relative",
            display: "flex",
            alignItems: "flex-start",
            justifyContent: "space-between",
        },
    });
});
