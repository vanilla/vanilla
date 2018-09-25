/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import DropDownItem, { IDropDownItem } from "@library/components/dropdown/items/DropDownItem";
import classNames from "classnames";

export default class DropDownItemSeparator extends React.Component<IDropDownItem> {
    public render() {
        return (
            <DropDownItem className={classNames("dropDown-separator", this.props.className)}>
                <hr className="sr-only" />
            </DropDownItem>
        );
    }
}
