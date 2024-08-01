/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { oneColumnVariables } from "@library/layout/Section.variables";
import { useThemeCache } from "@library/styles/styleUtils";
import { calc } from "csx";
import { css } from "@emotion/css";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { sticky } from "@library/styles/styleHelpersPositioning";

export const adminLayoutClasses = useThemeCache(() => {
    const panelLayoutVars = oneColumnVariables();
    const mediaQueries = titleBarVariables().mediaQueries();

    const container = css({
        display: "flex",
        flex: 1,
        background: "#fbfcff",
    });

    const layout = css({
        display: "flex",
        flex: 1,
        minHeight: "100%",
    });

    const leftPanel = css({
        display: "flex",
        flexDirection: "column",
        borderRight: singleBorder(),
        // Make sure the border stretches to the full height.
        height: "100%",
        paddingTop: 48,
    });

    const twoColLeftPanel = css({
        // Add some extra padding on here so absolute position collapsers
        // don't get cutoff by the the overflow: auto.
        width: 280,
        marginLeft: -24,
        paddingLeft: 24,
        ...sticky(),
        top: 16,
        paddingTop: 48,
        // Critical for the sticky to work.
        alignSelf: "flex-start",
        maxHeight: `calc(100vh - ${titleBarVariables().fullHeight}px)`,
        overflow: "auto",
    });

    const noLeftPanel = css(
        {
            marginLeft: calc(
                `((100vw - ${panelLayoutVars.contentWidth + panelLayoutVars.gutter.full}px)/2 + ${
                    panelLayoutVars.gutter.full
                }px)/-1`,
            ),
        },
        mediaQueries.customBreakPoint({ marginLeft: -panelLayoutVars.gutter.full }, panelLayoutVars.contentWidth),
        mediaQueries.compact({ marginLeft: -panelLayoutVars.gutter.size, marginRight: -panelLayoutVars.gutter.size }),
    );

    const rightPanel = css(
        {
            display: "flex",
            flexDirection: "column",
            flex: 1,
            backgroundColor: "#fff",
            marginRight: calc(
                `((100vw - ${panelLayoutVars.contentWidth + panelLayoutVars.gutter.full}px)/2 + ${
                    panelLayoutVars.gutter.full
                }px)/-1`,
            ),
        },
        mediaQueries.customBreakPoint({ marginRight: -panelLayoutVars.gutter.full }, panelLayoutVars.contentWidth),
        mediaQueries.compact({ marginRight: -panelLayoutVars.gutter.size, marginLeft: -panelLayoutVars.gutter.size }),
    );

    const content = css(
        {
            maxWidth: panelLayoutVars.contentWidth - 240 - 36 - 2,
            height: "100%",
            ...Mixins.padding(
                Variables.spacing({
                    vertical: 18,
                    horizontal: 18,
                }),
            ),
        },
        mediaQueries.compact(Mixins.padding(Variables.spacing({ horizontal: panelLayoutVars.gutter.size }))),
    );

    const contentNoLeftPanel = css({
        maxWidth: "none",
    });

    // These are some overrides to make our three panel layout
    // closer resemble the old dashboard three panel view
    const threePanel = css({
        "&&": {
            marginTop: 10,
        },

        "& main": {
            marginTop: 0,
        },

        "& .leftColumn": {
            borderRight: singleBorder(),
            "& > div > div": {
                width: "calc(100% + 24px)",
                paddingLeft: 24,
                marginLeft: -24,
                // overflow: "visible",
                "& > div": {
                    paddingRight: 0,
                    "& aside": {
                        border: 0,
                    },
                },
            },
        },

        // The content panel
        "& .mainColumn": {
            backgroundColor: "#fff",
        },
        // The right panel
        "& .rightColumn": {
            borderLeft: singleBorder(),
        },

        "& .panelArea": {
            paddingLeft: 0,
            paddingRight: 0,
        },

        "* .middleTopArea": {
            // So sticky headers work.
            display: "contents",
        },
    });

    const helpText = css({
        fontSize: "inherit!important",
        flex: 1,
        marginTop: 0,
        ...Mixins.padding(
            Variables.spacing({
                vertical: 18,
                horizontal: 18,
                top: 48,
            }),
        ),

        "& .panelWidget *:first-child": {
            marginTop: 0,
        },
    });

    const adjustedContainerPadding = css(
        mediaQueries.customBreakPoint({ paddingRight: 0 }, panelLayoutVars.contentWidth),
        mediaQueries.compact({ paddingLeft: 0 }),
    );

    return {
        container,
        layout,
        leftPanel,
        twoColLeftPanel,
        noLeftPanel,
        rightPanel,
        content,
        contentNoLeftPanel,
        threePanel,
        helpText,
        adjustedContainerPadding,
    };
});
