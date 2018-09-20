/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import DropDownItem from "@library/components/dropdown/DropDownItem";

export default class DropDownSeparator extends React.Component {
    public render() {
        return (
            <DropDownItem className="dropDown-separator">
                <hr className="sr-only"/>
            </DropDownItem>
        );
    }
}
