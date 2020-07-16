/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, variableFactory, styleFactory } from "@library/styles/styleUtils";
import { IThemeVariables } from "@library/theming/themeReducer";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import {
    absolutePosition,
    borders,
    colorOut,
    EMPTY_BORDER,
    EMPTY_FONTS,
    EMPTY_SPACING,
    fonts,
    paddings,
    pointerEvents,
    singleBorder,
    unit,
} from "@library/styles/styleHelpers";
import { clickableItemStates } from "@dashboard/compatibilityStyles/clickableItemHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { TextTransformProperty } from "csstype";
import { important, percent } from "csx";

export const userCardVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeVars = variableFactory("popupUserCard", forcedVars);
    const globalVars = globalVariables();

    const container = makeVars("container", {
        spacing: globalVars.gutter.size / 2,
    });

    const actionContainer = makeVars("actionContainer", {
        spacing: 12,
    });

    const button = makeVars("button", {
        minWidth: 120,
        mobile: {
            minWidth: 140,
        },
    });

    const buttonContainer = makeVars("buttonContainer", {
        padding: globalVars.gutter.half,
    });

    const name = makeVars("name", {
        size: globalVars.fonts.size.large,
        weight: globalVars.fonts.weights.bold,
    });

    const label = makeVars("label", {
        border: {
            ...EMPTY_BORDER,
            color: globalVars.mainColors.primary,
            radius: 3,
        },
        padding: {
            ...EMPTY_SPACING,
            vertical: 2,
            horizontal: 10,
        },
        font: {
            ...EMPTY_FONTS,
            color: globalVars.mainColors.primary,
            size: 10,
            transform: "uppercase" as TextTransformProperty,
        },
    });

    const containerWithBorder = makeVars("containerWithBorder", {
        color: colorOut(globalVars.border.color),
        ...paddings({
            horizontal: container.spacing * 2,
            vertical: container.spacing * 4,
        }),
    });

    const count = makeVars("count", {
        size: 28,
    });

    const header = makeVars("header", {
        height: 32,
    });

    const date = makeVars("date", {
        padding: globalVars.gutter.size,
    });

    const email = makeVars("email", {
        color: colorOut(globalVars.mainColors.fg),
    });

    return {
        container,
        button,
        buttonContainer,
        actionContainer,
        name,
        label,
        containerWithBorder,
        count,
        header,
        date,
        email,
    };
});

export const userCardClasses = useThemeCache((props: { compact?: boolean } = {}) => {
    const style = styleFactory("popupUserCard");
    const vars = userCardVariables();
    const linkColors = clickableItemStates();
    const mediaQueries = layoutVariables().mediaQueries();
    const globalVars = globalVariables();

    const container = style("container", {
        display: "flex",
        flexDirection: "row",
        alignItems: "stretch",
        justifyContent: "center",
        ...paddings({
            all: vars.container.spacing,
        }),
        flexWrap: "wrap",
    });

    const actionContainer = style("actionContainer", {
        $nest: {
            "&&": {
                ...paddings({
                    horizontal: vars.actionContainer.spacing,
                    top: vars.container.spacing,
                    bottom: vars.actionContainer.spacing * 2 - vars.container.spacing,
                }),
                ...mediaQueries.oneColumnDown({
                    ...paddings({
                        vertical: vars.actionContainer.spacing * 2 - vars.container.spacing,
                        horizontal: vars.actionContainer.spacing,
                    }),
                }).$nest,
            },
        },
    });

    const containerWithBorder = style("containerWithBorder", {
        borderTop: `1px solid ${vars.containerWithBorder.color}`,
    });

    const button = style(
        "button",
        {
            $nest: {
                "&&": {
                    minWidth: unit(vars.button.minWidth),
                },
            },
        },
        mediaQueries.oneColumnDown({
            $nest: {
                "&&": {
                    minWidth: unit(vars.button.mobile.minWidth),
                },
            },
        }),
    );

    const buttonContainer = style("buttonContainer", {
        ...paddings({
            all: vars.container.spacing,
        }),
    });

    const name = style(
        "name",
        {
            fontSize: vars.name.size,
            fontWeight: vars.name.weight,
            width: percent(100),
            textAlign: "center",
        },
        mediaQueries.oneColumnDown({
            fontSize: vars.name.size * 1.25,
        }),
    );

    const label = style("label", {
        ...fonts(vars.label.font),
        ...paddings(vars.label.padding),
        ...borders(vars.label.border),
        marginTop: unit(vars.container.spacing),
    });

    const stat = style("stat", {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        flexGrow: 1,
        maxWidth: percent(50),
    });

    const statLabel = style("statLabel", {
        marginTop: unit(2),
        marginBottom: unit(3),
        ...fonts({
            size: globalVars.fonts.size.small,
            lineHeight: globalVars.lineHeights.condensed,
        }),
        ...mediaQueries.oneColumnDown({
            ...fonts({
                size: globalVars.fonts.size.medium,
                lineHeight: globalVars.lineHeights.condensed,
            }),
        }),
    });

    const statLeft = style("statLeft", {
        borderRight: singleBorder({}),
        ...paddings({
            vertical: vars.container.spacing,
            right: globalVars.spacer.size / 2,
            left: globalVars.spacer.size,
        }),
    });

    const statRight = style("statRight", {
        ...paddings({
            vertical: vars.container.spacing,
            right: globalVars.spacer.size,
            left: globalVars.spacer.size / 2,
        }),
    });

    const count = style("count", {
        marginTop: unit(3),
        ...fonts({
            size: vars.count.size,
            lineHeight: globalVars.lineHeights.condensed,
        }),
    });

    const header = style("header", {
        position: "relative",
        height: unit(vars.header.height),
    });

    const section = style("section", {});

    const email = style("email", {
        $nest: {
            "&&&": {
                ...fonts({
                    color: globalVars.mainColors.fg,
                    size: globalVars.fonts.size.small,
                    align: "center",
                }),
                marginTop: unit(vars.container.spacing * 1.8),
                width: percent(100),
            },
            ...linkColors.$nest,
        },
    });

    const date = style("date", {
        padding: vars.buttonContainer.padding,
    });

    const formElementsVars = formElementsVariables();

    const close = style("close", {
        $nest: {
            "&&&": {
                ...absolutePosition.topRight(),
                width: unit(formElementsVars.sizing.height),
                height: unit(formElementsVars.sizing.height),
                ...mediaQueries.oneColumnDown({
                    height: unit(formElementsVars.sizing.height),
                }),
            },
        },
    });

    const userPhoto = style("userPhoto", {
        margin: "auto",
        display: "block",
    });

    const link = style("link", {
        color: "inherit",
        fontSize: "inherit",
        $nest: {
            "&.isLoading": {
                cursor: important("wait"),
                ...pointerEvents("auto"),
            },
        },
    });

    return {
        container,
        containerWithBorder,
        button,
        buttonContainer,
        name,
        label,
        stat,
        count,
        header,
        section,
        email,
        date,
        statLabel,
        statLeft,
        statRight,
        close,
        userPhoto,
        actionContainer,
        link,
    };
});
