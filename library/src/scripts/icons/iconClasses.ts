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

    return {
        standard,
        newFolder,
        fileType,
        attachmentError,
        vanillaLogo,
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

    return {
        standard,
        newFolder,
        fileType,
        attachmentError,
        vanillaLogo,
    };
});
