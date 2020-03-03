/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { themeBuilderClasses } from "@library/forms/themeEditor/themeBuilderStyles";
import classNames from "classnames";

export interface IThemeBuilderBlock {
    label: string;
    labelID: string;
    undo?: boolean;
    children: React.ReactChild;
    inputWrapClass?: string;
}

export default function ThemeBuilderBlock(props: IThemeBuilderBlock) {
    const classes = themeBuilderClasses();
    return (
        <div className={classes.inputBlock}>
            <label htmlFor={props.labelID} className={classes.label}>
                {props.label}
            </label>
            <span className={classNames(classes.inputWrap, props.inputWrapClass)}>{props.children}</span>
        </div>
    );
}
