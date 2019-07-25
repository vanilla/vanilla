/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";

interface IProps {
    className?: string;
    legacyMode?: boolean;
}

/**
 * Implements separator line for element in a paragraph menu
 */
export default class DropDownItemSeparator extends React.Component<IProps> {
    public render() {
        const classes = richEditorClasses(!!this.props.legacyMode);
        return (
            <div className={classNames(this.props.className, classes.separator)}>
                <ScreenReaderContent>
                    <hr role="separator" />
                </ScreenReaderContent>
            </div>
        );
    }
}
