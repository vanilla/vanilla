/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { ThemeInputText } from "@library/forms/themeEditor/ThemeInputText";

export function CustomFontFamily() {
    return <ThemeInputText varKey={"global.fonts.customFont.name"} />;
}
