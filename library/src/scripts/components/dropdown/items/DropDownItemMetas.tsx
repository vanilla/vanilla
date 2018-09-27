/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import DropDownItem from "@library/components/dropdown/items/DropDownItem";
import classNames from "classnames";
import Sentence, { IWord } from "@library/components/Sentence";

interface IProps {
    className?: string;
    contents: IWord[] | string;
}

/**
 * Implements meta type of item for DropDownMenu
 */
export default class DropDownItemMetas extends React.Component<IProps> {
    public render() {
        return (
            <DropDownItem className="dropDown-metasItem">
                <Sentence contents={this.props.contents} />
            </DropDownItem>
        );
    }
}
