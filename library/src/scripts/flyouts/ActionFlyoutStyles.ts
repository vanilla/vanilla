import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { unit } from "@library/styles/styleHelpers";

export const actionFlyoutVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const themeVars = variableFactory("actionFlyout");

    const rootActionFlyout = themeVars("rootActionFlyout", {
        bottom: 40,
        right: 24,
    });

    const clickActionFlyout = themeVars("clickActionFlyout", {
        marginTop: 24,
    });

    const itemActionFlyout = themeVars("itemAction", {
        marginRight: 6,
        marginTop: 16,
    });

    const buttonActionFlyout = themeVars("buttonActionFlyout", {
        borderRadius: 21.5,
        height: 44,
        paddingLeft: 18,
        paddingRight: 18,
    });

    const iconActionFlyout = themeVars("iconActionFlyout", {
        marginRight: 10,
    });

    return {
        rootActionFlyout,
        clickActionFlyout,
        itemActionFlyout,
        buttonActionFlyout,
        iconActionFlyout,
    };
});

export const actionFlyoutClasses = useThemeCache(() => {
    const style = styleFactory("actionFlyout");
    const vars = actionFlyoutVariables();

    const root = style({
        position: "fixed",
        bottom: unit(vars.rootActionFlyout.bottom),
        right: unit(vars.rootActionFlyout.right),
        textAlign: "right",
    });

    const click = style({
        marginTop: unit(vars.clickActionFlyout.marginTop),
        display: "inline-flex",
        alignItems: "center",
        borderRadius: "50%",
        boxShadow: "0 5px 10px 0",
        cursor: "pointer",
    });

    const clickOpen = style({
        transform: "rotate(-45deg)",
    });

    const item = style({
        marginRight: unit(vars.itemActionFlyout.marginRight),
        marginTop: unit(vars.itemActionFlyout.marginTop),
    });

    const button = style({
        borderRadius: unit(vars.buttonActionFlyout.borderRadius),
        boxShadow: "0 5px 10px 0 rgba(0, 0, 0, 0.3)",
        backgroundColor: "#ffffff",
        height: unit(vars.buttonActionFlyout.height),
        display: "inline-flex",
        alignItems: "center",
        paddingLeft: unit(vars.buttonActionFlyout.paddingLeft),
        paddingRight: unit(vars.buttonActionFlyout.paddingRight),
        cursor: "pointer",
    });

    const icon = style({
        display: "inline-block",
        marginRight: unit(vars.iconActionFlyout.marginRight),
    });

    return {
        root,
        item,
        click,
        clickOpen,
        button,
        icon,
    };
});
