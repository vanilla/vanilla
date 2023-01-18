import { css, cx } from "@emotion/css";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { sticky } from "@library/styles/styleHelpersPositioning";
import { useThemeCache } from "@library/styles/styleUtils";
import { Variables } from "@library/styles/Variables";
import { calc } from "csx";
import { CSSProperties } from "react";

export const adminTitleBarClasses = useThemeCache((props?: { zIndex: CSSProperties["zIndex"] }) => {
    const { zIndex } = props ?? {};
    const panelLayoutVars = oneColumnVariables();
    const mediaQueries = titleBarVariables().mediaQueries();

    const root = css({
        background: "#fbfcff",
        borderBottom: "1px solid #dddee0",
        position: "sticky",
        top: titleBarVariables().fullHeight + 1,
        zIndex: zIndex ?? 1,
    });

    const container = useThemeCache((useTwoColumnContainer?: boolean) =>
        css(
            {
                display: "flex",
                flexDirection: "column",
                ...Mixins.margin(
                    Variables.spacing({
                        vertical: 12,
                        left: 28,
                        right: useTwoColumnContainer
                            ? calc(
                                  `(100vw - ${panelLayoutVars.contentWidth + panelLayoutVars.gutter.full}px)/2 + ${
                                      panelLayoutVars.gutter.full
                                  }px`,
                              )
                            : 28,
                    }),
                ),
            },

            mediaQueries.customBreakPoint({ marginRight: panelLayoutVars.gutter.full }, panelLayoutVars.contentWidth),
            mediaQueries.compact({ marginRight: panelLayoutVars.gutter.size, marginLeft: panelLayoutVars.gutter.size }),
        ),
    );

    const titleAndActionsContainer = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "space-between",
    });

    const titleAndDescriptionContainer = css({});

    const titleWrap = css({
        fontSize: "20px",
        fontWeight: "bold",
        color: "#555a62",
        marginBottom: 0,
        display: "flex",
        alignItems: "center",
        flexWrap: "wrap",
    });

    const title = css({
        marginRight: 16,
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

        "&& a": {
            [`&:hover, &:active, &:focus, &.focus-visible`]: {
                textDecoration: "none",
            },
        },
    });

    return {
        root,
        container,
        titleWrap,
        title,
        titleAndActionsContainer,
        titleAndDescriptionContainer,
        actionsWrapper,
        descriptionWrapper,
        description,
    };
});

export const adminEditTitleBarClasses = useThemeCache(() => {
    const panelLayoutVars = oneColumnVariables();
    const mediaQueries = titleBarVariables().mediaQueries();

    const editingContainerWrapper = css({
        ...sticky(),
        top: 0,
        zIndex: 3,
        boxShadow: "0 2px 4px rgba(0,0,0,0.2)",
        marginBottom: 1,
        background: "#fff",
    });

    const editingContainer = cx(
        adminTitleBarClasses().container(true),
        css({
            background: "#fff",
        }),
        css({
            marginTop: 0,
            marginBottom: 0,
        }),
        css({
            height: 48,
            justifyContent: "space-between",
        }),
    );

    const backButton = css({
        width: "auto",
        display: "flex",
        alignItems: "center",
        outlineOffset: 4,
    });

    const saveButton = css({
        outlineOffset: 4,
    });

    const editTitle = css({
        flex: 1,
        display: "flex",
        justifyContent: "center",
        flexDirection: "row",
        alignItems: "center",
        fontSize: "14px",
        fontWeight: 600,
    });

    const editTitleOnMobile = css({
        marginTop: 20,
        flex: 0,
    });

    const editableInput = (inputLength: number) =>
        css({
            padding: "8px 4px",
            textAlign: "center",
            fontSize: 14,
            fontWeight: 600,
            width: inputLength ? `${inputLength + 2}ch` : "14ch",
            borderColor: "transparent",
        });

    const editActions = css({
        height: "100%",
        flex: 1,
        justifyContent: "flex-end",
        display: "flex",
        flexDirection: "row",
        alignItems: "center",

        "& > *": {
            marginLeft: 16,
        },
    });

    const wrapper = css({
        height: "100%",
        width: "100%",
        margin: "0 auto",
        display: "flex",
        flexDirection: "row",
        alignItems: "stretch",
    });

    const editLeftActions = css({
        flex: 1,
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
    });

    const noLeftPanel = css(
        {
            marginLeft: calc(
                `(100vw - ${panelLayoutVars.contentWidth + panelLayoutVars.gutter.full}px)/2 + ${
                    panelLayoutVars.gutter.full
                }px`,
            ),
        },
        mediaQueries.customBreakPoint({ marginLeft: panelLayoutVars.gutter.full }, panelLayoutVars.contentWidth),
        mediaQueries.compact({ marginLeft: panelLayoutVars.gutter.size, marginRight: panelLayoutVars.gutter.size }),
    );

    return {
        editLeftActions,
        editingContainer,
        editingContainerWrapper,
        backButton,
        saveButton,
        editTitle,
        editTitleOnMobile,
        editableInput,
        editActions,
        wrapper,
        noLeftPanel,
    };
});
