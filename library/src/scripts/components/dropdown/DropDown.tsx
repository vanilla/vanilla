/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { dropDownMenu } from "@library/components/Icons";
import { getRequiredID } from "@library/componentIDs";
import DropDownItem from "@library/components/dropdown/items/DropDownItem";
import PopoverController from "@library/components/PopoverController";
import DropDownContents from "@library/components/dropdown/items/DropDownContents";

export interface IProps {
    name: string;
    children: React.ReactNode;
    className?: string;
}

export interface IState {
    id: string;
    open: boolean;
}

export default class DropDown extends React.PureComponent<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "dropDown"),
            open: false,
        };
    }

    public render() {
        const children = React.Children.map(this.props.children, child => {
            return <DropDownItem>{child}</DropDownItem>;
        });

        return (
            <PopoverController
                id={this.state.id}
                classNameRoot="dropDown"
                icon={dropDownMenu()}
                buttonClasses="button-icon"
            >
                {params => {
                    return (
                        <DropDownContents {...params}>
                            <ul className="dropDownItems">{children}</ul>
                        </DropDownContents>
                    );
                }}
            </PopoverController>
        );
    }
}
