/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { variableFactory, styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { IThemeVariables } from "@library/theming/themeReducer";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { absolutePosition, pointerEvents, singleBorder } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { important, percent } from "csx";
import { buttonVariables } from "@library/forms/Button.variables";
import { buttonResetMixin } from "@library/forms/buttonMixins";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { ButtonTypes } from "@library/forms/buttonTypes";

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
            minWidth: 0,
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
        border: Variables.border({
            color: globalVars.mainColors.primary,
            radius: 3,
        }),
        padding: Variables.spacing({
            vertical: 2,
            horizontal: 10,
        }),
        font: Variables.font({
            color: globalVars.mainColors.primary,
            size: 10,
            transform: "uppercase",
        }),
    });

    const containerWithBorder = makeVars("containerWithBorder", {
        color: ColorsUtils.colorOut(globalVars.border.color),
        ...Mixins.padding({
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
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
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
    const linkColors = Mixins.clickable.itemState();
    const mediaQueries = layoutVariables().mediaQueries();
    const globalVars = globalVariables();

    const container = style("container", {
        display: "flex",
        flexDirection: "row",
        alignItems: "stretch",
        justifyContent: "center",
        ...Mixins.padding({
            all: vars.container.spacing,
        }),
        flexWrap: "wrap",
    });

    const metaContainer = style("metaContainer", {
        ...Mixins.padding({
            all: vars.container.spacing,
        }),
    });

    const row = style("row", {
        display: "flex",
        justifyContent: "center",
    });

    const actionContainer = style("actionContainer", {
        ...{
            "&&": {
                ...Mixins.padding({
                    horizontal: vars.actionContainer.spacing,
                    top: vars.container.spacing,
                    bottom: vars.actionContainer.spacing * 2 - vars.container.spacing,
                }),
                ...mediaQueries.oneColumnDown({
                    ...Mixins.padding({
                        vertical: vars.actionContainer.spacing * 2 - vars.container.spacing,
                        horizontal: vars.actionContainer.spacing,
                    }),
                }),
            },
        },
    });

    const containerWithBorder = style("containerWithBorder", {
        borderTop: `1px solid ${vars.containerWithBorder.color}`,
    });

    const avatar = style("avatar", {
        ...buttonResetMixin(),
    });

    // Fetch button styles
    const buttonStyles = generateButtonStyleProperties({
        buttonTypeVars: buttonVariables().standard,
    });
    // Create new class with same styles
    const buttonClass = style(buttonStyles);

    const button = style(
        "button",
        {
            maxWidth: percent(100),
            ...{
                "&&": {
                    minWidth: styleUnit(vars.button.minWidth),
                },
            },
        },
        mediaQueries.oneColumnDown({
            ...{
                "&&": {
                    width: percent(100),
                    minWidth: styleUnit(vars.button.mobile.minWidth),
                },
            },
        }),
    );

    const buttonContainer = style(
        "buttonContainer",
        {
            maxWidth: percent(100),
            ...Mixins.padding({
                all: vars.container.spacing,
            }),
        },
        mediaQueries.oneColumnDown({
            ...{
                "&&": {
                    flexGrow: 1,
                    flexBasis: percent(50),
                },
            },
        }),
    );

    const name = style(
        "name",
        {
            margin: "auto",
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
        ...Mixins.padding(vars.label.padding),
        ...Mixins.font(vars.label.font),
        ...Mixins.border(vars.label.border),
        ...Mixins.margin({
            top: vars.container.spacing,
            horizontal: "auto",
        }),
    });

    const stat = style("stat", {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        flexGrow: 1,
        maxWidth: percent(50),
    });

    const statLabel = style("statLabel", {
        marginTop: styleUnit(2),
        marginBottom: styleUnit(3),
        ...Mixins.font({
            size: globalVars.fonts.size.small,
            lineHeight: globalVars.lineHeights.condensed,
        }),
        ...mediaQueries.oneColumnDown({
            ...Mixins.font({
                size: globalVars.fonts.size.medium,
                lineHeight: globalVars.lineHeights.condensed,
            }),
        }),
    });

    const statLeft = style("statLeft", {
        borderRight: singleBorder({}),
        ...Mixins.padding({
            vertical: vars.container.spacing,
            right: globalVars.spacer.size / 2,
            left: globalVars.spacer.size,
        }),
    });

    const statRight = style("statRight", {
        ...Mixins.padding({
            vertical: vars.container.spacing,
            right: globalVars.spacer.size,
            left: globalVars.spacer.size / 2,
        }),
    });

    const count = style("count", {
        marginTop: styleUnit(3),
        ...Mixins.font({
            size: vars.count.size,
            lineHeight: globalVars.lineHeights.condensed,
        }),
    });

    const header = style("header", {
        position: "relative",
        height: styleUnit(vars.header.height),
    });

    const section = style("section", {});

    const email = style("email", {
        ...{
            "&&&&": {
                ...Mixins.font({
                    color: globalVars.mainColors.fg,
                    size: globalVars.fonts.size.small,
                    align: "center",
                }),
                display: "inline-flex",
                margin: "auto",
                marginTop: styleUnit(vars.container.spacing * 1.8),
            },
            ...linkColors,
        },
    });

    const date = style("date", {
        padding: vars.buttonContainer.padding,
        fontSize: styleUnit(12),
    });

    const formElementsVars = formElementsVariables();

    const close = style("close", {
        ...{
            "&&&": {
                ...absolutePosition.topRight(),
                width: styleUnit(formElementsVars.sizing.height),
                height: styleUnit(formElementsVars.sizing.height),
                ...mediaQueries.oneColumnDown({
                    height: styleUnit(formElementsVars.sizing.height),
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
        ...{
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
        avatar,
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
        metaContainer,
        row,
    };
});
