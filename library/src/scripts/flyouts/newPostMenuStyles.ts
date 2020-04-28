import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { unit, colorOut, absolutePosition, negativeUnit } from "@library/styles/styleHelpers";
import { iconClasses } from "@library/icons/iconStyles";
import { translateX } from "csx";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { relative } from "path";

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
            right: 6,
        },
    });

    const action = themeVars("action", {
        borderRadius: 21.5,
        padding: {
            horizontal: 18,
        },
        size: {
            height: 44,
        },
    });

    const toggle = themeVars("toggle", {
        size: 56,
        on: {
            rotation: `-315deg`,
        },
        position: {
            top: 24,
        },
    });

    const label = themeVars("label", {
        margin: {
            left: 10,
        },
    });

    return {
        position,
        item,
        action,
        toggle,
        label,
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
        textAlign: "right",
    });

    const isOpen = style("isOpen", {
        transform: `rotate(${vars.toggle.on.rotation})`,
    });

    const item = style("item", {
        marginTop: unit(vars.item.position.top),
        marginRight: unit(vars.item.position.right),
    });

    const action = style("action", {
        borderRadius: unit(vars.action.borderRadius),
        ...shadowHelper().dropDown(),
        height: unit(vars.action.size.height),
        backgroundColor: colorOut(globalVars.mainColors.bg),
        paddingLeft: unit(vars.action.padding.horizontal),
        paddingRight: unit(vars.action.padding.horizontal),
        display: "inline-flex",
        alignItems: "center",
        $nest: {
            "&:hover": {
                backgroundColor: colorOut(globalVars.mainColors.primary),
            },
        },
    });

    const toggle = style("toggle", {
        display: "inline-flex",
        alignItems: "center",
        justifyItems: "center",
        borderRadius: "50%",
        ...shadowHelper().dropDown(),
        marginTop: unit(vars.toggle.position.top),
        height: unit(vars.toggle.size),
        width: unit(vars.toggle.size),
        backgroundColor: colorOut(globalVars.mainColors.primary),
        $nest: {
            [`& .${isOpen} .${iconClasses().newPostMenuIcon}`]: {
                transform: translateX(vars.toggle.on.rotation),
            },
        },
    });

    const label = style("label", {
        marginLeft: unit(vars.label.margin.left),
        display: "inline-block",
    });

    const container = style("container", {
        height: "100vh",
        width: "100vw",
        position: "relative",
        zIndex: "9999",
    });

    const containerOpen = style("containerOpen", {
        backgroundColor: colorOut(globalVars.mainColors.fg),
    });

    const containerClose = style("containerClose", {
        backgroundColor: colorOut(globalVars.mainColors.bg),
    });

    return {
        root,
        item,
        action,
        isOpen,
        toggle,
        label,
        container,
        containerOpen,
        containerClose,
    };
});
