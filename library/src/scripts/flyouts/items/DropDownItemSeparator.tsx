/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";

interface IProps {
    className?: string;
}

/**
 * Implements line separator type of item for DropDownMenu
 */
export default class DropDownItemSeparator extends React.Component<IProps> {
    public render() {
        const classes = dropDownClasses();
        return <li role="separator" className={classNames(this.props.className, classes.separator)} />;
    }
}
