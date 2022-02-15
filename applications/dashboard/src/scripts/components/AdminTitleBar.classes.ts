import { css } from "@emotion/css";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/styleUtils";
import { Variables } from "@library/styles/Variables";
import { calc } from "csx";

const adminTitleBarClasses = useThemeCache(() => {
    const panelLayoutVars = oneColumnVariables();
    const mediaQueries = titleBarVariables().mediaQueries();

    const root = css({
        background: "#fbfcff",
        borderBottom: "1px solid #dddee0",
    });

    const container = css(
        {
            display: "flex",
            flexDirection: "column",
            ...Mixins.margin(
                Variables.spacing({
                    vertical: 12,
                    left: 28,
                    right: calc(
                        `(100vw - ${panelLayoutVars.contentWidth + panelLayoutVars.gutter.full}px)/2 + ${
                            panelLayoutVars.gutter.full
                        }px`,
                    ),
                }),
            ),
        },

        mediaQueries.customBreakPoint({ marginRight: panelLayoutVars.gutter.full }, panelLayoutVars.contentWidth),
        mediaQueries.compact({ marginRight: panelLayoutVars.gutter.size, marginLeft: panelLayoutVars.gutter.size }),
    );

    const titleAndActionsContainer = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "space-between",
    });

    const titleAndDescriptionContainer = css({});

    const title = css({
        fontSize: "20px",
        fontWeight: "bold",
        color: "#555a62",
        marginBottom: 0,
    });

    const actionsWrapper = css({ display: "flex", flexDirection: "row", alignItems: "center" });

    const descriptionWrapper = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        marginTop: "8px",
    });

    const description = css({
        ...Mixins.font({
            ...globalVariables().fontSizeAndWeightVars("medium", "normal"),
            lineHeight: 24 / 14,
        }),
    });

    return {
        root,
        container,
        title,
        titleAndActionsContainer,
        titleAndDescriptionContainer,
        actionsWrapper,
        descriptionWrapper,
        description,
    };
});

export default adminTitleBarClasses;
