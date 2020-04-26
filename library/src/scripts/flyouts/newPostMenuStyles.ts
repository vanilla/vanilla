import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { unit, colorOut, absolutePosition, negativeUnit } from "@library/styles/styleHelpers";
import { iconClasses } from "@library/icons/iconStyles";
import { translateX } from "csx";
import { shadowHelper } from "@library/styles/shadowHelpers";

export const newPostMenuVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = variableFactory("newPostMenu");

    const position = themeVars("position", {
        bottom: 40,
        right: 24,
    });

    const item = themeVars("itemAction", {
        position: {
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

    return {
        position,
        item,
        toggle,
    };
});

export const newPostMenuClasses = useThemeCache(() => {
    const style = styleFactory("newPostMenu");
    const vars = newPostMenuVariables();
    const globalVars = globalVariables();

    const root = style({
        position: "fixed",
        bottom: unit(vars.position.bottom),
        right: unit(vars.position.right),
    });

    const isOpen = style("isOpen", {
        transform: `rotate(${vars.toggle.on.rotation})`,
    });

    const item = style("item", {
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
        height: unit(vars.toggle.size),
        width: unit(vars.toggle.size),
        backgroundColor: colorOut(globalVars.mainColors.primary),
        $nest: {
            [`& .${isOpen} .${iconClasses().newPostMenuIcon}`]: {
                transform: translateX(vars.toggle.on.rotation),
            },
        },
    });

    const label = style("label", {});

    const menu = style("menu", {
        ...absolutePosition.bottomRight("100%"),
        display: "flex",
        flexDirection: "column",
        alignItems: "flex-end",
        justifyContent: "flex-end",
    });

    return {
        root,
        item,
        action,
        isOpen,
        toggle,
        label,
        menu,
    };
});
