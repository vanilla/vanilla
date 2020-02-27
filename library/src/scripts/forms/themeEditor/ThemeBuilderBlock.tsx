/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { themeBuilderClasses } from "@library/forms/themeEditor/themeBuilderStyles";

export interface IThemeBuilderBlock {
    label: string;
    labelID: string;
    undo?: boolean;
    children: React.ReactChild;
}

export default function ThemeBuilderBlock(props: IThemeBuilderBlock) {
    const classes = themeBuilderClasses();
    return (
        <div className={classes.inputBlock}>
            <label htmlFor={props.labelID} className={classes.label}>
                {props.label}
            </label>
            {props.undo && <span className={classes.undoWrap}>{/*TODO: undo button*/}</span>}
            <span className={classes.inputWrap}>{props.children}</span>
        </div>
    );
}
