/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { em } from "csx";
import { unit } from "@library/styles/styleHelpers";

export const iconVariables = useThemeCache(() => {
    const themeVars = variableFactory("defaultIconSizes");

    const defaultIcon = themeVars("defaultIcon", {
        width: 24,
        height: 24,
    });

    const newFolderIcon = themeVars("newFolderIcon", {
        width: 17,
        height: 14.67,
    });

    return { defaultIcon, newFolderIcon };
});

export const iconStyles = useThemeCache(() => {
    const vars = iconVariables();
    const style = styleFactory("iconSizes");

    const standard = style("defaultIcon", {
        width: unit(vars.defaultIcon.width),
        height: unit(vars.defaultIcon.height),
    });

    const newFolder = style("newFolder", {
        width: unit(vars.newFolderIcon.width),
        height: unit(vars.newFolderIcon.height),
    });

    return {
        standard,
        newFolder,
    };
});
