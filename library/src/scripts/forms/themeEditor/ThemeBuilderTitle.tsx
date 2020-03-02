/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { themeBuilderClasses } from "@library/forms/themeEditor/themeBuilderStyles";
import { t } from "@vanilla/i18n/src";

export default function ThemeBuilderTitle() {
    return <h2 className={themeBuilderClasses().title}>{t("Global Styles")}</h2>;
}
