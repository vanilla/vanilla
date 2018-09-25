/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { dropDownMenu } from "@library/components/Icons";
import { getRequiredID } from "@library/componentIDs";
import PopoverController from "@library/components/PopoverController";
import DropDownContents from "@library/components/dropdown/items/DropDownContents";

export interface IProps {
    name: string;
    children: React.ReactNode;
    className?: string;
    stickTop?: boolean; // Adjusts the flyout position vertically
    stickRight?: boolean; // Adjusts the flyout position horizontally
    icon?: JSX.Element;
}

export interface IState {
    id: string;
}

export default class DropDown extends React.PureComponent<IProps, IState> {
    public static defaultProps = {
        stickRight: true,
        stickTop: true,
        icon: dropDownMenu(),
    };

    public constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "dropDown"),
        };
    }

    public render() {
        return (
            <PopoverController
                id={this.state.id}
                classNameRoot="dropDown"
                icon={this.props.icon!}
                buttonClasses="button button-icon"
            >
                {params => {
                    return (
                        <DropDownContents
                            isPositionedFromRight={this.props.stickRight!}
                            isPositionedFromTop={this.props.stickTop!}
                            {...params}
                        >
                            <ul className="dropDownItems">{this.props.children}</ul>
                        </DropDownContents>
                    );
                }}
            </PopoverController>
        );
    }
}
