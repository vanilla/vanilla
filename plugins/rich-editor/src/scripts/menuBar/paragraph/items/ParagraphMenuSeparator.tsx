/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { paragraphMenuBarClasses } from "@rich-editor/menuBar/paragraph/paragraphMenuBarStyles";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";

interface IProps {
    className?: string;
}

/**
 * Implements separator line for element in a paragraph menu
 */
export default class DropDownItemSeparator extends React.Component<IProps> {
    public render() {
        const classes = paragraphMenuBarClasses();
        return (
            <div className={classNames(this.props.className, classes.separator)}>
                <ScreenReaderContent>
                    <hr role="separator" />
                </ScreenReaderContent>
            </div>
        );
    }
}
