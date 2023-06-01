/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleUnit } from "@library/styles/styleUnit";
import { useThemeCache } from "@library/styles/themeCache";
import { calc, important, percent } from "csx";
import { richEditorVariables } from "@library/editor/richEditorVariables";
import { inputMixin } from "@library/forms/inputStyles";
import { css } from "@emotion/css";

export const insertLinkClasses = useThemeCache(() => {
    const vars = richEditorVariables();

    const root = css({
        position: "relative",
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "center",
        maxWidth: styleUnit(vars.insertLink.width),
        width: percent(100),
        paddingLeft: 0,
        overflow: "hidden",
    });

    const input = css({
        ...inputMixin(),
        zIndex: 2,
        border: important("0"),
        marginBottom: important("0"),
        flexGrow: 1,
        maxWidth: calc(`100% - ${styleUnit(vars.menuButton.size - (vars.menuButton.size - 12) / 2)}`), // 12 is from the size set SCSS file.
    });

    return { root, input };
});
