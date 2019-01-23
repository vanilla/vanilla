/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { dropDownMenu } from "@library/components/icons/common";
import { getRequiredID } from "@library/componentIDs";
import PopoverController from "@library/components/PopoverController";
import DropDownContents from "./DropDownContents";
import { ButtonBaseClass } from "@library/components/forms/Button";
import Heading from "@library/components/Heading";
import SmartAlign from "@library/components/SmartAlign";
import classNames from "classnames";
import FlexSpacer from "@library/components/FlexSpacer";
import CloseButton from "@library/components/CloseButton";
import { Frame } from "@library/components/frame";

export interface IProps {
    id?: string;
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
    toggleButtonClassName?: string;
    setExternalButtonRef?: (ref: React.RefObject<HTMLButtonElement>) => void;
    onVisibilityChange?: (isVisible: boolean) => void;
    openAsModal?: boolean;
    title?: string;
}

export interface IState {
    selectedText: string;
}

/**
 * Creates a drop down menu
 */
export default class DropDown extends React.Component<IProps, IState> {
    private id;
    public static defaultProps = {
        openAsModal: false,
    };
    public constructor(props) {
        super(props);
        this.id = getRequiredID(props, "dropDown");
        this.state = {
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
        const { title } = this.props;
        return (
            <PopoverController
                id={this.id}
                className={this.props.className}
                buttonBaseClass={this.props.buttonBaseClass || ButtonBaseClass.CUSTOM}
                name={this.props.name}
                buttonContents={this.props.buttonContents || dropDownMenu()}
                buttonClassName={this.props.buttonClassName}
                selectedItemLabel={this.selectedText}
                disabled={this.props.disabled}
                setExternalButtonRef={this.props.setExternalButtonRef}
                toggleButtonClassName={this.props.toggleButtonClassName}
                onVisibilityChange={this.props.onVisibilityChange}
                openAsModal={!!this.props.openAsModal}
            >
                {params => {
                    return (
                        <DropDownContents
                            {...params}
                            id={this.id + "-handle"}
                            parentID={this.id}
                            className={this.props.contentsClassName}
                            onClick={this.doNothing}
                            renderLeft={!!this.props.renderLeft}
                            renderAbove={!!this.props.renderAbove}
                            openAsModal={this.props.openAsModal}
                        >
                            {title ? (
                                <header className="frameHeader">
                                    <FlexSpacer className="frameHeader-leftSpacer" />
                                    <SmartAlign>
                                        <Heading title={title} className="dropDown-title" />
                                    </SmartAlign>
                                    <div className="frameHeader-closePosition">
                                        <CloseButton
                                            className="frameHeader-close"
                                            onClick={params.closeMenuHandler}
                                            baseClass={ButtonBaseClass.CUSTOM}
                                        />
                                    </div>
                                </header>
                            ) : null}
                            <ul className="dropDownItems">{this.props.children}</ul>
                        </DropDownContents>
                    );
                }}
            </PopoverController>
        );
    }

    private doNothing = e => {
        e.stopPropagation();
    };
}
