/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { dropDownMenu } from "@library/components/icons/common";
import { getRequiredID } from "@library/componentIDs";
import PopoverController from "@library/components/PopoverController";
import DropDownContents from "./DropDownContents";
import { ButtonBaseClass } from "@library/components/forms/Button";
import classNames from "classnames";

export interface IProps {
    id: string;
    name?: string;
    children: React.ReactNode;
    className?: string;
    renderAbove?: boolean; // Adjusts the flyout position vertically
    renderLeft?: boolean; // Adjusts the flyout position horizontally
    describedBy?: string;
    contentsClassName?: string;
    buttonContents?: React.ReactNode;
    buttonClassName?: string;
    buttonBaseClass?: ButtonBaseClass;
    disabled?: boolean;
}

export interface IState {
    id: string;
    selectedText: string;
}

/**
 * Creates a drop down menu
 */
export default class DropDown extends React.Component<IProps, IState> {
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
                disabled={this.props.disabled}
            >
                {params => {
                    return (
                        <DropDownContents
                            id={this.state.id + "-handle"}
                            parentID={this.state.id}
                            className={this.props.contentsClassName}
                            onClick={params.closeMenuHandler}
                            renderLeft={!!this.props.renderLeft}
                            renderAbove={!!this.props.renderAbove}
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
