/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/forms/formElementStyles";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { singleBorder } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { useThemeCache } from "@library/styles/themeCache";
import { userCardVariables } from "@library/features/userCard/UserCard.variables";
import { percent } from "csx";
import { css, injectGlobal, cx } from "@emotion/css";

export const userCardClasses = useThemeCache((props: { compact?: boolean; zIndex?: number } = {}) => {
    const vars = userCardVariables();
    const mediaQueries = oneColumnVariables().mediaQueries();
    const globalVars = globalVariables();

    // Global for reach
    injectGlobal({
        "[data-reach-popover]": {
            zIndex: props.zIndex ?? 1050, // Just like our modals.
        },
    });

    const container = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "stretch",
        justifyContent: "center",
        ...Mixins.padding({
            all: vars.container.spacing,
        }),
        flexWrap: "wrap",
    });

    const containerWithBorder = cx(
        container,
        css({
            borderTop: `1px solid ${vars.containerWithBorder.color}`,
        }),
    );

    const row = css({
        display: "flex",
        width: "100%",
        justifyContent: "center",
    });

    const button = css({
        maxWidth: percent(100),
        ...{
            "&&": {
                minWidth: styleUnit(vars.button.minWidth),
            },
        },

        ...mediaQueries.oneColumnDown({
            ...{
                "&&": {
                    width: percent(100),
                    minWidth: styleUnit(vars.button.mobile.minWidth),
                },
            },
        }),
    });

    const buttonsContainer = css({
        ...Mixins.padding({
            horizontal: vars.container.spacing / 2,
        }),
        flexWrap: "wrap",
    });

    const buttonContainer = css({
        maxWidth: percent(100),
        ...Mixins.padding({
            top: 0,
            bottom: vars.container.spacing,
            horizontal: vars.container.spacing / 2,
        }),

        ...mediaQueries.oneColumnDown({
            "&&": {
                flexGrow: 1,
                flexBasis: percent(50),
            },
        }),
    });

    const name = css(
        {
            ...Mixins.margin(vars.name.margin),
            ...Mixins.font(vars.name.font),
            width: percent(100),
            textAlign: "center",
        },
        mediaQueries.oneColumnDown({
            fontSize: (vars.name.font.size! as number) * 1.25,
        }),
    );

    const label = css({
        ...Mixins.padding(vars.label.padding),
        ...Mixins.font(vars.label.font),
        ...Mixins.border(vars.label.border),
        ...Mixins.margin(vars.label.margin),
    });

    const statLeft = css({
        borderRight: singleBorder({}),
        ...Mixins.padding({
            right: globalVars.spacer.size / 2,
            left: globalVars.spacer.size,
        }),
    });

    const statRight = css({
        ...Mixins.padding({
            right: globalVars.spacer.size,
            left: globalVars.spacer.size / 2,
        }),
    });

    const header = css({
        position: "relative",
    });

    const linkColors = Mixins.clickable.itemState({ default: vars.headerLink.color });
    const headerLink = css({
        ...linkColors,
        "&&": {
            minHeight: vars.headerLink.minHeight,
            display: "inline-flex",
            alignItems: "center",
            ...Mixins.font(vars.headerLink.font),
            ...Mixins.margin(vars.headerLink.margin),
        },
    });

    const metas = css({
        "&&": { textAlign: "center" },
    });

    const metaItem = css({
        ...Mixins.margin({
            vertical: 0,
        }),
    });

    const formElementsVars = formElementsVariables();

    const close = css({
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

    const userPhoto = css({
        ...Mixins.margin({
            top: vars.container.spacing,
        }),
        display: "block",
    });

    const message = css({
        ...Mixins.font(vars.message.font),
        ...Mixins.margin(vars.message.margin),
    });

    return {
        container,
        containerWithBorder,
        buttonsContainer,
        button,
        buttonContainer,
        name,
        label,
        header,
        headerLink,
        statLeft,
        statRight,
        close,
        userPhoto,
        row,
        metas,
        metaItem,
        message,
    };
});
