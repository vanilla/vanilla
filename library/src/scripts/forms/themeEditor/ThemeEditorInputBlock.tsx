/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";

import { themeEditorClasses } from "@library/forms/themeEditor/themeEditorStyles";

export interface IThemeEditorInputBlock {
    label: string;
    labelID: string;
    undo?: boolean;
    children: React.ReactChild;
}

export default function ThemeEditorInputBlock(props: IThemeEditorInputBlock) {
    const classes = themeEditorClasses();
    return (
        <div className={classes.root}>
            <label htmlFor={props.labelID} className={classes.label}>
                {props.label}
            </label>
            <span className={classes.undoWrap}>{/*TODO: undo button*/}</span>
            <span className={classes.inputWrap}>{props.children}</span>
        </div>
    );
}
