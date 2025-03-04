/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import classNames from "classnames";
import React from "react";
import { translationGridClasses } from "./TranslationGridStyles";

interface IProps {
    /** Content to put in the left cell of the grid. */
    leftCell: React.ReactNode;

    /** Content to put in the right cell of the grid. */
    rightCell: React.ReactNode;

    /** Whether or not the row is the first one. Used for alignment */
    isFirst?: boolean;

    /** Whether or not the row is the last one. Used for alignment */
    isLast?: boolean;

    /** Extra CSS class to apply to the row wrapper. */
    className?: string;
}

/**
 * Component representing the layout structure for a row in the grid.
 */
export function TranslationGridRow(props: IProps) {
    const { leftCell, rightCell, isFirst, isLast } = props;
    const classes = translationGridClasses();
    return (
        <div
            className={classNames(classes.row, props.className, {
                [classes.isFirst]: isFirst,
                [classes.isLast]: isLast,
            })}
        >
            <div
                className={classNames(classes.leftCell, {
                    [classes.isFirst]: isFirst,
                    [classes.isLast]: isLast,
                })}
            >
                {leftCell}
            </div>
            <div
                className={classNames(classes.rightCell, {
                    [classes.isFirst]: isFirst,
                    [classes.isLast]: isLast,
                })}
            >
                {rightCell}
            </div>
        </div>
    );
}
