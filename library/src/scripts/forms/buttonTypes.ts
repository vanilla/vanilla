export const ButtonTypes = {
    STANDARD: "standard",
    PRIMARY: "primary",
    OUTLINE: "outline",
    TRANSPARENT: "transparent",
    TRANSLUCID: "translucid",
    CUSTOM: "custom",
    RESET: "reset",
    RADIO: "radioAsButton",
    TEXT: "text",
    TEXT_PRIMARY: "textPrimary",
    ICON: "icon",
    ICON_MENUBAR: "iconMenubar",
    ICON_COMPACT: "iconCompact",
    TITLEBAR_LINK: "titleBarLink",
    DASHBOARD_STANDARD: "dashboardStandard",
    DASHBOARD_PRIMARY: "dashboardPrimary",
    DASHBOARD_SECONDARY: "dashboardSecondary",
    DASHBOARD_LINK: "dashboardLink",
    NOT_STANDARD: "notStandard",
    INPUT: "input",
} as const;
export const ButtonType = ButtonTypes;

export type ButtonTypes = (typeof ButtonTypes)[keyof typeof ButtonTypes];
export type ButtonType = ButtonTypes;
