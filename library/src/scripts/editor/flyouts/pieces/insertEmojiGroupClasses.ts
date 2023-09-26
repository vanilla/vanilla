/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

export const emojiGroupsClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("emojiGroups");

    const root = style({
        display: "flex",
        flexWrap: "nowrap",
        justifyContent: "center",
    });

    const icon = style("icon", {
        display: "block",
        position: "relative",
        margin: "auto",
        padding: 0,
        width: styleUnit(globalVars.icon.sizes.default),
        height: styleUnit(globalVars.icon.sizes.default),
    });

    return { root, icon };
});
