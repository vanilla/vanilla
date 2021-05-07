/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { buttonVariables } from "@library/forms/Button.variables";
import { buttonResetMixin } from "@library/forms/buttonMixins";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { panelLayoutVariables } from "@library/layout/PanelLayout.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { pointerEvents, singleBorder } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { userCardVariables } from "@library/features/userCard/UserCard.variables";
import { important, percent } from "csx";
import { css, injectGlobal } from "@emotion/css";

export const userCardClasses = useThemeCache((props: { compact?: boolean } = {}) => {
    const style = styleFactory("popupUserCard");
    const vars = userCardVariables();
    const mediaQueries = panelLayoutVariables().mediaQueries();
    const globalVars = globalVariables();

    // Global for reach

    injectGlobal({
        "[data-reach-popover]": {
            zIndex: 1051, // Get above our modals.
        },
    });

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
            fontSize: (vars.name.size! as number) * 1.25,
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
            ...globalVars.fontSizeAndWeightVars("small"),
            lineHeight: globalVars.lineHeights.condensed,
        }),
        ...mediaQueries.oneColumnDown({
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("medium"),
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

    const linkColors = Mixins.clickable.itemState({ default: globalVars.mainColors.fg });
    const email = style("email", {
        ...linkColors,
        "&&": {
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("small"),
                align: "center",
            }),
            display: "inline-flex",
            margin: "auto",
            marginTop: styleUnit(vars.container.spacing * 1.8),
        },
    });

    const metas = css({
        textAlign: "center",
    });

    const formElementsVars = formElementsVariables();

    const close = style("close", {
        ...{
            "&&&": {
                ...Mixins.absolute.topRight(),
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
        statLabel,
        statLeft,
        statRight,
        close,
        userPhoto,
        actionContainer,
        link,
        metaContainer,
        row,
        metas,
    };
});
