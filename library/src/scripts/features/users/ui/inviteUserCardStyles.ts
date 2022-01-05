/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { variableFactory, styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { IThemeVariables } from "@library/theming/themeReducer";
import { globalVariables } from "@library/styles/globalStyleVars";
import { percent } from "csx";
import { styleUnit } from "@library/styles/styleUnit";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { css } from "@emotion/css";

export const userCardVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeVars = variableFactory("inviteUserCard", forcedVars);
    const globalVars = globalVariables();

    const body = makeVars("body", {
        padding: {
            size: globalVars.gutter.size,
        },
    });

    const button = makeVars("button", {
        mobile: {
            width: percent(47),
        },
    });

    const buttonGroup = makeVars("buttonGroup", {
        padding: {
            top: globalVars.gutter.size,
            bottom: globalVars.gutter.size,
        },
    });

    const message = makeVars("message", {
        padding: {
            bottom: globalVars.gutter.size,
        },
    });

    return { body, button, buttonGroup, message };
});

export const userCardClasses = useThemeCache((props: { compact?: boolean } = {}) => {
    const mediaQueries = oneColumnVariables().mediaQueries();
    const vars = userCardVariables();

    const body = css({
        padding: vars.body.padding.size,
    });

    const button = css(
        {},
        mediaQueries.oneColumnDown({
            ...{
                "&&": {
                    width: vars.button.mobile.width,
                },
            },
        }),
    );

    const buttonGroup = css(
        {
            display: "flex",
            justifyContent: "flex-end",
            paddingTop: vars.buttonGroup.padding.top,
            paddingBottom: vars.buttonGroup.padding.bottom,
            ...{
                "&>*:first-child": {
                    marginRight: styleUnit(20),
                },
            },
        },
        mediaQueries.oneColumnDown({
            ...{
                "&&": {
                    justifyContent: "space-between",
                },
                "&>*:first-child": {
                    marginRight: styleUnit(0),
                },
            },
        }),
    );

    const message = css({
        paddingBottom: vars.message.padding.bottom,
    });

    const textbox = css({});

    const users = css({
        maxHeight: styleUnit(100),
        overflowY: "scroll",
    });

    return { body, button, buttonGroup, message, textbox, users };
});
