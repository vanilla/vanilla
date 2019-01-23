/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import DropDownItem from "./DropDownItem";

interface IProps {
    className?: string;
}

/**
 * Implements line separator type of item for DropDownMenu
 */
export default class DropDownItemSeparator extends React.Component<IProps> {
    public render() {
        return (
            <DropDownItem className={classNames("dropDown-separator", this.props.className)}>
                <hr className="sr-only" />
            </DropDownItem>
        );
    }
}
