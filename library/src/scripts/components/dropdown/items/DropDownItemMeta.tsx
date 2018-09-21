/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import DropDownItem from "@library/components/dropdown/items/DropDownItem";

export interface IProps {
    className?: string;
    metas: React.ReactNode;
}

export default class DropDownItemMeta extends React.Component<IProps> {
    private hasChildren: boolean;

    public constructor(props) {
        super(props);
        this.hasChildren = props.metas.length > 0;
    }

    public render() {
        const temp = `temp`;
        if (this.hasChildren) {
            return (
                <DropDownItem className={this.props.className}>
                    {temp}
                </DropDownItem>
            );
        } else {
            return null;
        }
    }
}
