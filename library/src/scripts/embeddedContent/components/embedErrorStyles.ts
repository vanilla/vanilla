/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { flexHelper } from "@library/styles/styleHelpers";
import { px } from "csx";

export const embedErrorClasses = useThemeCache(() => {
    const style = styleFactory("embedError");
    const renderErrorRoot = style("renderErrorRoot", {
        display: "block",
        textAlign: "start",
    });

    const renderErrorIconLink = style("renderErrorIconLink", {
        paddingLeft: px(4),
        verticalAlign: "middle",
    });

    return { renderErrorRoot, renderErrorIconLink };
});
