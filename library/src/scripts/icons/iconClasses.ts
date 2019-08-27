/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { em } from "csx";
import { margins, unit, colorOut } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";

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
        width: 12,
        height: 12,
    });

    const warning = themeVars("warning", {
        width: 16,
        height: 16,
        color: "#f5af15",
    });

    const settings = themeVars("settings", {
        width: 20,
        height: 18,
    });

    const search = themeVars("settings", {
        width: 18,
        height: 20,
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
        width: 20,
        height: 20,
    });

    const closeCompact = themeVars("closeCompact", {
        width: 16,
        height: 16,
    });

    const closeTiny = themeVars("closeTiny", {
        width: 10,
        height: 10,
    });

    const chevronLeftCompact = themeVars("chevronLeftCompact", {
        width: 12,
        height: 21,
    });

    const selectedCategory = themeVars("selectedCategory", {
        width: 16.8,
        height: 13,
    });

    const signIn = themeVars("signIn", {
        width: 24,
        height: 18,
    });

    const chevronUp = themeVars("selectedCategory", {
        width: 51,
        height: 17,
    });

    const plusCircle = themeVars("plusCircle", {
        width: 14,
        height: 14,
    });

    const deleteIcon = themeVars("deleteIcon", {
        width: 24,
        height: 24,
    });

    const editIcon = themeVars("editIcon", {
        width: 24,
        height: 24,
    });

    const categoryIcon = themeVars("categoryIcon", {
        width: 18,
        height: 18,
        opacity: 0.8,
    });

    return {
        standard,
        newFolder,
        fileType,
        attachmentError,
        vanillaLogo,
        compact,
        settings,
        warning,
        search,
        notifications,
        messages,
        user,
        userWarning,
        close,
        closeCompact,
        closeTiny,
        chevronLeftCompact,
        selectedCategory,
        signIn,
        chevronUp,
        plusCircle,
        categoryIcon,
        deleteIcon,
        editIcon,
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
        paddingRight: unit(1),
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

    const warning = style("warning", {
        width: unit(vars.warning.width),
        height: unit(vars.warning.height),
        color: colorOut(vars.warning.color),
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

    // Same as close, but without extra padding
    const closeCompact = style("closeCompact", {
        width: unit(vars.closeCompact.width),
        height: unit(vars.closeCompact.height),
    });

    // For really small close buttons, like on tokens
    const closeTiny = style("closeTiny", {
        display: "block",
        width: unit(vars.closeTiny.width),
        height: unit(vars.closeTiny.height),
        margin: "auto",
    });

    const chevronLeftCompact = style("chevronLeftCompact", {
        width: unit(vars.chevronLeftCompact.width),
        height: unit(vars.chevronLeftCompact.height),
    });

    const selectedCategory = style("selectedCategory", {
        width: unit(vars.selectedCategory.width),
        height: unit(vars.selectedCategory.height),
    });

    const signIn = style("signIn", {
        width: unit(vars.signIn.width),
        height: unit(vars.signIn.height),
    });

    const chevronUp = style("chevronUp", {
        width: unit(vars.chevronUp.width),
        height: unit(vars.chevronUp.height),
    });

    const plusCircle = style("plusCircle", {
        width: unit(vars.plusCircle.width),
        height: unit(vars.plusCircle.height),
    });

    const categoryIcon = style("categoryIcon", {
        width: unit(vars.categoryIcon.width),
        height: unit(vars.categoryIcon.height),
        opacity: vars.categoryIcon.opacity,
    });

    const deleteIcon = style("deleteIcon", {
        width: unit(vars.deleteIcon.width),
        height: unit(vars.deleteIcon.height),
    });

    const editIcon = style("editIcon", {
        width: unit(vars.editIcon.width),
        height: unit(vars.editIcon.height),
    });

    return {
        standard,
        newFolder,
        warning,
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
        closeCompact,
        closeTiny,
        chevronLeftCompact,
        selectedCategory,
        signIn,
        chevronUp,
        plusCircle,
        categoryIcon,
        deleteIcon,
        editIcon,
    };
});
