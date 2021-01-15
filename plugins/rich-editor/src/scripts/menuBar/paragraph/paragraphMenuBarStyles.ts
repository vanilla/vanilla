/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory } from "@library//styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { richEditorVariables } from "@rich-editor/editor/richEditorVariables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, percent } from "csx";
import { appearance } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { buttonResetMixin } from "@vanilla/library/src/scripts/forms/buttonMixins";

export const paragraphMenuCheckRadioClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = richEditorVariables();
    const style = styleFactory("paragraphMenuCheckRadio");

    const group = style("group", {
        marginBottom: styleUnit(9),
    });

    const checkRadio = style("checkRadio", {
        ...buttonResetMixin(),
        ...appearance(),
        border: 0,
        display: "flex",
        alignItems: "center",
        width: percent(100),
        minHeight: styleUnit(30),
        userSelect: "none",
        padding: 0,
        outline: 0,
        ...{
            "&:hover": {
                backgroundColor: ColorsUtils.colorOut(globalVars.states.hover.highlight),
                zIndex: 1,
            },
            "&:active": {
                backgroundColor: ColorsUtils.colorOut(globalVars.states.active.highlight),
                zIndex: 1,
            },
            "&:focus": {
                backgroundColor: ColorsUtils.colorOut(globalVars.states.hover.highlight),
                zIndex: 1,
            },
        },
    });
    const check = style("check", {});
    const radio = style("radio", {});
    const checked = style("checked", {});
    const separator = style("separator", {});
    const icon = style("icon", {
        width: styleUnit(vars.menuButton.size),
        flexBasis: styleUnit(vars.menuButton.size),
    });
    const checkRadioLabel = style("checkRadioLabel", {
        flexGrow: 1,
        maxWidth: calc(`100% - ${styleUnit(vars.menuButton.size * 2)}`),
        textAlign: "left",
    });
    const checkRadioSelected = style("checkRadioSelected", {
        width: styleUnit(vars.menuButton.size),
        flexBasis: styleUnit(vars.menuButton.size),
    });

    return {
        group,
        checkRadio,
        check,
        radio,
        checked,
        separator,
        icon,
        checkRadioLabel,
        checkRadioSelected,
    };
});
