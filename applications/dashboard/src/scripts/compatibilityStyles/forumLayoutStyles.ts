/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { important, percent, px } from "csx";
import { paddings, unit } from "@library/styles/styleHelpers";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { legacyLayout } from "@library/layout/types/legacy";
import { containerMainStyles, containerMainMediaQueries } from "@library/layout/components/containerStyles";

// For forumVariables, see legacyLayout()

export const forumLayoutCSS = () => {
    const globalVars = globalVariables();
    const vars = legacyLayout();
    const mediaQueries = vars.mediaQueries();

    cssOut(
        `.Container, body.Section-Event.NoPanel .Frame-content > .Container`,
        mediaQueries.tablet({
            ...paddings({
                horizontal: globalVars.gutter.half,
            }),
        }),
    );

    cssOut(
        `body.Section-Event.NoPanel .Frame-content > .Container`,
        containerMainStyles(vars.contentWidth() as number | string),
    );

    cssOut(`.Frame-content .HomepageTitle`, {
        $nest: lineHeightAdjustment(),
    });

    cssOut(
        `.Frame-row`,
        {
            display: "flex",
            flexWrap: "nowrap",
            justifyContent: "space-between",
            ...paddings({
                horizontal: globalVars.gutter.half,
            }),
            $nest: {
                "& > *": {
                    ...paddings({
                        horizontal: globalVars.gutter.half,
                    }),
                },
            },
        },
        mediaQueries.oneColumnDown({
            flexWrap: important("wrap"),
        }),
        mediaQueries.tablet({
            ...paddings({
                horizontal: 0,
            }),
        }),
    );

    cssOut(
        `.Panel`,
        {
            width: unit(vars.panelPaddedWidth()),
            ...paddings({
                vertical: globalVars.gutter.half,
            }),
        },
        mediaQueries.oneColumnDown({
            width: percent(100),
        }),
    );

    cssOut(
        `.Content.MainContent`,
        {
            width: unit(vars.main.width),
            ...paddings({
                all: globalVars.gutter.half,
            }),
        },
        mediaQueries.oneColumnDown({
            width: percent(100),
        }),
    );

    cssOut(
        `.Container`,
        containerMainStyles(vars.contentWidth() as number | string),
        containerMainMediaQueries(mediaQueries),
    );

    cssOut(`.Frame-row`, {
        display: "flex",
        flexWrap: "nowrap",
        ...paddings({
            all: globalVars.gutter.half,
        }),
        $nest: {
            "& > *": {
                ...paddings({
                    horizontal: globalVars.gutter.half,
                }),
            },
        },
    });
};
