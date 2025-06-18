import { buttonClasses, buttonUtilityClasses } from "./Button.styles";
import { titleBarClasses } from "@library/headers/TitleBar.classes";
import { ButtonTypes, type ButtonType } from "@library/forms/buttonTypes";

export function getClassForButtonType(type: ButtonType | undefined) {
    if (type) {
        const buttonUtils = buttonUtilityClasses();
        const classes = buttonClasses();
        switch (type) {
            case ButtonTypes.STANDARD:
                return classes.standard;
            case ButtonTypes.TEXT:
                return classes.text;
            case ButtonTypes.TEXT_PRIMARY:
                return classes.textPrimary;
            case ButtonTypes.ICON:
                return buttonUtils.buttonIcon;
            case ButtonTypes.ICON_MENUBAR:
                return buttonUtils.buttonIconMenuBar;
            case ButtonTypes.ICON_COMPACT:
                return buttonUtils.buttonIconCompact;
            case ButtonTypes.PRIMARY:
                return classes.primary;
            case ButtonTypes.TRANSPARENT:
                return classes.transparent;
            case ButtonTypes.OUTLINE:
                return classes.outline;
            case ButtonTypes.TRANSLUCID:
                return classes.translucid;
            case ButtonTypes.TITLEBAR_LINK:
                return titleBarClasses().linkButton;
            case ButtonTypes.CUSTOM:
                return classes.custom;
            case ButtonTypes.RESET:
                return buttonUtilityClasses().reset;
            case ButtonTypes.DASHBOARD_STANDARD:
                return "btn";
            case ButtonTypes.DASHBOARD_PRIMARY:
                return "btn btn-primary";
            case ButtonTypes.DASHBOARD_SECONDARY:
                return "btn btn-secondary";
            case ButtonTypes.DASHBOARD_LINK:
                return "btn btn-link";
            case ButtonTypes.NOT_STANDARD:
                return classes.notStandard;
            case ButtonTypes.INPUT:
                return classes.input;
            default:
                return "";
        }
    } else {
        return "";
    }
}
