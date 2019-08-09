/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { em } from "csx";
import { unit } from "@library/styles/styleHelpers";

export const iconVariables = useThemeCache(() => {
    const themeVars = variableFactory("defaultIconSizes");

    const standard = themeVars("defaultIcon", {
        width: 24,
        height: 24,
    });

    const fileType = themeVars("defaultIcon", {
        width: 16,
        height: 16,
    });

    const newFolder = themeVars("newFolderIcon", {
        width: 17,
        height: 14.67,
    });

    const attachmentError = themeVars("attachmentError", {
        width: 20,
        height: 18,
    });

    const vanillaLogo = themeVars("vanillaLogo", {
        width: 296.866,
        height: 119.993,
    });

    const compact = themeVars("compact", {
        width: 16,
        height: 16,
    });

    const settings = themeVars("settings", {
        width: 20,
        height: 18,
    });

    const search = themeVars("settings", {
        width: 13.312,
        height: 13.311,
    });

    const notifications = themeVars("settings", {
        width: 18,
        height: 20,
    });

    const messages = themeVars("messages", {
        width: 20.051,
        height: 14.016,
    });

    const user = themeVars("user", {
        width: 20,
        height: 20,
    });

    const userWarning = themeVars("userWarning", {
        width: 40,
        height: 40,
    });
    const close = themeVars("close", {
        width: 12,
        height: 12,
    });

    return {
        standard,
        newFolder,
        fileType,
        attachmentError,
        vanillaLogo,
        compact,
        settings,
        search,
        notifications,
        messages,
        user,
        userWarning,
        close,
    };
});

export const iconClasses = useThemeCache(() => {
    const vars = iconVariables();
    const style = styleFactory("iconSizes");

    const standard = style("defaultIcon", {
        width: unit(vars.standard.width),
        height: unit(vars.standard.height),
    });

    const fileType = style("fileType", {
        width: unit(vars.fileType.width),
        height: unit(vars.fileType.height),
    });

    const newFolder = style("newFolder", {
        width: unit(vars.newFolder.width),
        height: unit(vars.newFolder.height),
    });

    const attachmentError = style("attachmentError", {
        width: unit(vars.attachmentError.width),
        height: unit(vars.attachmentError.height),
    });

    const vanillaLogo = style("vanillaLogo", {
        width: unit(vars.vanillaLogo.width),
        height: unit(vars.vanillaLogo.height),
    });

    const compact = style("compact", {
        width: unit(vars.compact.width),
        height: unit(vars.compact.height),
    });

    const settings = style("settings", {
        width: unit(vars.settings.width),
        height: unit(vars.settings.height),
    });

    const search = style("search", {
        width: unit(vars.search.width),
        height: unit(vars.search.height),
    });

    const notifications = style("notifications", {
        width: unit(vars.notifications.width),
        height: unit(vars.notifications.height),
    });

    const messages = style("messages", {
        width: unit(vars.messages.width),
        height: unit(vars.messages.height),
    });

    const user = style("user", {
        width: unit(vars.user.width),
        height: unit(vars.user.height),
    });

    const userWarning = style("userWarning", {
        width: unit(vars.userWarning.width),
        height: unit(vars.userWarning.height),
    });

    const close = style("close", {
        width: unit(vars.close.width),
        height: unit(vars.close.height),
    });

    return {
        standard,
        newFolder,
        fileType,
        attachmentError,
        vanillaLogo,
        compact,
        settings,
        search,
        notifications,
        messages,
        user,
        userWarning,
        close,
    };
});
