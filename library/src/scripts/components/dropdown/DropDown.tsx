/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { dropDownMenu } from "@library/components/Icons";
import { getRequiredID } from "@library/componentIDs";
import PopoverController from "@library/components/PopoverController";
import DropDownContents from "./DropDownContents";
import { ButtonBaseClass } from "@library/components/forms/Button";

export interface IProps {
    id: string;
    name?: string;
    children: React.ReactNode;
    className?: string;
    stickTop?: boolean; // Adjusts the flyout position vertically
    stickRight?: boolean; // Adjusts the flyout position horizontally
    describedBy?: string;
    buttonContents?: React.ReactNode;
    buttonClassName?: string;
    buttonBaseClass?: ButtonBaseClass;
}

export interface IState {
    id: string;
    selectedText: string;
}

/**
 * Creates a drop down menu
 */
export default class DropDown extends React.PureComponent<IProps, IState> {
    public static defaultProps = {
        stickRight: true,
        stickTop: true,
    };

    public constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "dropDown"),
            selectedText: "",
        };
    }

    public setSelectedText(selectedText) {
        this.setState({
            selectedText,
        });
    }

    public get selectedText(): string {
        return this.state.selectedText;
    }

    public render() {
        return (
            <PopoverController
                id={this.state.id}
                classNameRoot="dropDown"
                buttonBaseClass={this.props.buttonBaseClass || ButtonBaseClass.CUSTOM}
                name={this.props.name}
                buttonContents={this.props.buttonContents || dropDownMenu()}
                buttonClassName={this.props.buttonClassName}
                selectedItemLabel={this.selectedText}
            >
                {params => {
                    return (
                        <DropDownContents
                            id={this.state.id + "-handle"}
                            parentID={this.state.id}
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
