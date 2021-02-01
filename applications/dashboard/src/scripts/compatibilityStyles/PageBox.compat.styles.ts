/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { MixinsFoundation } from "@library/styles/MixinsFoundation";
import { useThemeCache } from "@library/styles/themeCache";
import { cssRaw } from "@vanilla/library/src/scripts/styles/styleShim";
import { Mixins } from "@library/styles/Mixins";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { percent } from "csx/lib/units";
import { lineHeightAdjustment } from "@library/styles/textUtils";

export const pageBoxCompatStyles = useThemeCache(() => {
    const globalVars = globalVariables();
    const mediaQueries = layoutVariables().mediaQueries();

    MixinsFoundation.contentBoxes(globalVars.contentBoxes);

    // Apply heading boxes

    const allHeadings = "& h1, & h2, & h3, & h4, & h5, & [role='heading']";
    cssRaw({
        ".pageHeadingBox.pageHeadingBox.pageHeadingBox": {
            margin: 0,
            padding: 0,
            display: "flex",
            alignItems: "center",
            flexWrap: "wrap",
            border: "none",
            justifyContent: "space-between",
            ...Mixins.padding(globalVars.headingBox.spacing),
            ...mediaQueries.oneColumnDown({
                ...Mixins.padding(globalVars.headingBox.mobileSpacing),
            }),

            "&:first-child": {
                paddingTop: 0,
            },

            ".PageTitle": {
                width: percent(100),
            },

            [allHeadings]: {
                margin: 0,
                padding: 0,
                fontSize: globalVars.fonts.size.largeTitle,
                ...lineHeightAdjustment(),

                "& > a": {
                    fontSize: "inherit",
                    fontWeight: "inherit",
                    color: "inherit",
                },
            },

            "& > p, & > .userContent, & > .P, & > .PageDescription": {
                width: "100%",
                ...Mixins.padding(globalVars.headingBox.descriptionSpacing),
            },
        },
        ".pageBox .pageHeadingBox.pageHeadingBox.pageHeadingBox, .pageHeadingBox.isSmall.isSmall.isSmall": {
            [allHeadings]: {
                fontSize: globalVars.fonts.size.title,
            },
        },
        ".pageBox .Empty": {
            margin: 0,
            border: "none",
        },
        ".pageBox + .PageControls": {
            marginTop: 16,
        },
    });
});
