/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";

export interface IThemeBuilderSection {
    label: string;
    children: React.ReactNode;
}

export function ThemeBuilderSection(props: IThemeBuilderSection) {
    const classes = themeBuilderClasses();
    return (
        <div className={classes.section}>
            <h3 className={classes.sectionTitle}>{props.label}</h3>
            {props.children}
        </div>
    );
}
