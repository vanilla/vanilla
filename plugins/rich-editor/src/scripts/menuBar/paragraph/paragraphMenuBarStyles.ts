/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { formElementsVariables } from "@library//forms/formElementStyles";
import { useThemeCache, styleFactory } from "@library//styles/styleUtils";
import { richEditorVariables } from "@rich-editor/editor/richEditorVariables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, percent } from "csx";
import { appearance, colorOut, unit } from "@library/styles/styleHelpers";
import { buttonResetMixin } from "@library/forms/buttonStyles";

export const paragraphMenuCheckRadioClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = richEditorVariables();
    const style = styleFactory("paragraphMenuCheckRadio");

    const group = style("group", {
        marginBottom: unit(9),
    });

    const checkRadio = style("checkRadio", {
        ...buttonResetMixin(),
        ...appearance(),
        border: 0,
        display: "flex",
        alignItems: "center",
        width: percent(100),
        minHeight: unit(30),
        userSelect: "none",
        padding: 0,
        outline: 0,
        $nest: {
            "&:hover": {
                backgroundColor: colorOut(globalVars.states.hover.highlight),
                zIndex: 1,
            },
            "&:active": {
                backgroundColor: colorOut(globalVars.states.active.highlight),
                zIndex: 1,
            },
            "&:focus": {
                backgroundColor: colorOut(globalVars.states.hover.highlight),
                zIndex: 1,
            },
        },
    });
    const check = style("check", {});
    const radio = style("radio", {});
    const checked = style("checked", {});
    const separator = style("separator", {});
    const icon = style("icon", {
        width: unit(vars.menuButton.size),
        flexBasis: unit(vars.menuButton.size),
    });
    const checkRadioLabel = style("checkRadioLabel", {
        flexGrow: 1,
        maxWidth: calc(`100% - ${unit(vars.menuButton.size * 2)}`),
        textAlign: "left",
    });
    const checkRadioSelected = style("checkRadioSelected", {
        width: unit(vars.menuButton.size),
        flexBasis: unit(vars.menuButton.size),
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
