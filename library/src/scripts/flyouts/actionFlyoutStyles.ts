import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { unit, colorOut } from "@library/styles/styleHelpers";
import { BorderRadiusProperty } from "csstype";
import { TLength } from "typestyle/lib/types";
import { iconClasses } from "@library/icons/iconStyles";
import { translateX } from "csx";
import { shadowHelper } from "@library/styles/shadowHelpers";

export const actionFlyoutVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = variableFactory("actionFlyout");

    const position = themeVars("position", {
        bottom: 40,
        right: 24,
    });

    const item = themeVars("itemAction", {
        position: {
            right: 6,
            top: 16,
        },
        padding: {
            horizontal: 18,
        },
        border: {
            radius: globalVars.borderType.formElements.buttons.radius,
        },
    });

    const toggle = themeVars("toggle", {
        size: 56,
        borderRadius: "50%",
        on: {
            rotation: `-315deg`,
        },
    });

    // const iconActionFlyout = themeVars("iconActionFlyout", {
    //     marginRight: 10,
    // });

    return {
        position,
        item,
        toggle,
    };
});

export const actionFlyoutClasses = useThemeCache(() => {
    const style = styleFactory("actionFlyout");
    const vars = actionFlyoutVariables();
    const globalVars = globalVariables();

    const root = style({
        position: "fixed",
        bottom: unit(vars.position.bottom),
        right: unit(vars.position.right),
    });

    const isOpen = style("isOpen", {});

    const item = style("item", {
        marginRight: unit(vars.item.position.right),
        marginTop: unit(vars.item.position.top),
    });

    const action = style("action", {
        borderRadius: unit(vars.item.border.radius),
        backgroundColor: colorOut(globalVars.mainColors.bg),

        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        cursor: "pointer",
    });

    const toggle = style("toggle", {
        display: "flex",
        ...shadowHelper().dropDown(),
        alignItems: "center",
        justifyItems: "center",
        borderRadius: "50%",
        // cursor: "pointer",
        height: unit(vars.toggle.size),
        width: unit(vars.toggle.size),
        backgroundColor: colorOut(globalVars.mainColors.primary),
        $nest: {
            [`& .${isOpen} .${iconClasses().postFlyout}`]: {
                transform: translateX(vars.toggle.on.rotation),
            },
        },
    });

    const label = style("label", {});

    return {
        root,
        item,
        action,
        isOpen,
        toggle,
        label,
    };
});
