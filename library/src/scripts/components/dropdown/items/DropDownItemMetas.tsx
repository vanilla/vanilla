/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import Sentence, { IWord } from "@library/components/translation/Sentence";
import DropDownItem from "./DropDownItem";

interface IProps {
    className?: string;
    children: React.ReactNode;
}

/**
 * Implements meta type of item for DropDownMenu
 */
export default class DropDownItemMetas extends React.Component<IProps> {
    public render() {
        return (
            <DropDownItem className={classNames("dropDown-metasItem", this.props.className)}>
                {this.props.children}
            </DropDownItem>
        );
    }
}
