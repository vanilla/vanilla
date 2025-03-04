/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import classNames from "classnames";
import React from "react";
import { translationGridClasses } from "./TranslationGridStyles";

interface IProps {
    /** The text to display */
    text: string;

    /** An additional CSS class to apply to the text. */
    className?: string;
}

/**
 * Text display wrapper styled for the translation grid.
 */
export function TranslationGridText(props: IProps) {
    const classes = translationGridClasses();
    const { text } = props;
    return <div className={classNames(classes.text, props.className)}>{text}</div>;
}
