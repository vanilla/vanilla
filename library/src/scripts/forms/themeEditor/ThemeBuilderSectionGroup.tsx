/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";

export interface IThemeBuilderSectionGroup {
    label: string;
    children: React.ReactNode;
}

export function ThemeBuilderSectionGroup(props: IThemeBuilderSectionGroup) {
    const classes = themeBuilderClasses();
    return (
        <div className={classes.subGroupSection}>
            <h3 className={classes.subGroupSectionTitle}>{props.label}</h3>
            {props.children}
        </div>
    );
}
