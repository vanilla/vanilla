/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";
import { percent, px } from "csx";
import { styleUnit } from "@library/styles/styleUnit";
import { css } from "@emotion/css";

export const selectBoxClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const buttonIcon = css({
        marginRight: "auto",
    });

    const toggle = css({
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        textAlign: "left",
        maxWidth: percent(100),
        border: 0,
        ...{
            "&.minimalStyles": {
                justifyContent: "center",
                ...{
                    [`.${buttonIcon}`]: {
                        marginRight: 0,
                    },
                },
            },
        },
    });

    const buttonItem = css({
        display: "flex",
        overflow: "hidden",
        alignItems: "center",
        justifyContent: "flex-start",
        textAlign: "left",
        maxWidth: percent(100),
        lineHeight: globalVars.lineHeights.condensed,
        paddingLeft: px(13.5),
        ...{
            "&[disabled]": {
                opacity: 1,
            },
        },
    });

    const selectBoxDropdown = css({});

    const checkContainer = css({
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        color: globalVars.mainColors.primary.toString(),
        width: percent(100),
        height: px(18),
        flexBasis: px(18),
        marginLeft: "auto",
    });

    const spacer = css({
        display: "block",
        width: px(18),
        height: px(18),
    });

    const itemLabel = css({
        display: "block",
        flexGrow: 1,
    });

    const offsetPadding = css({
        paddingTop: styleUnit(0),
        paddingBottom: styleUnit(0),
    });

    return {
        toggle,
        buttonItem,
        buttonIcon,
        selectBoxDropdown,
        checkContainer,
        spacer,
        itemLabel,
        offsetPadding,
    };
});
