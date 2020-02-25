/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { themeBuilderClasses } from "@library/forms/themeEditor/themeBuilderStyles";

export interface IThemeBuilderSubSection {
    label: string;
    children: React.ReactChild;
}

export default function ThemeBuilderSubSection(props: IThemeBuilderSubSection) {
    const classes = themeBuilderClasses();
    return (
        <div className={classes.subSection}>
            <h3 className={classes.subSectionTitle}>{props.label}</h3>
            {props.children}
        </div>
    );
}
