/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import DropDownItem from "@library/flyouts/items/DropDownItem";
import { CheckIcon } from "@library/icons/titleBar";

export interface IMenuRadioOption {
    name: string;
    value: string;
    selected?: boolean;
}

interface IProps {
    name: string;
    groupID: string; // the "name" of the radio button group
    className?: string;
    options: IMenuRadioOption[];
    onChange: () => void;
}

export interface IState {
    selectedValue: string;
}

/**
 * Implements radio button type of item for DropDownMenu. Note that visually this component doesn't look like radio buttons,
 * despite being radio buttons semantically and functionally.
 */
export default class DropDownItemRadio extends React.Component<IProps, IState> {
    private hasOptions: boolean;

    public constructor(props) {
        super(props);
        this.hasOptions = props.options && props.options.length > 0;
        let selectedIndex = 0;

        if (this.hasOptions && !props.selectedOption) {
            props.selectedOption = props.options[0].value;
            props.selectedOption.some((option, index) => {
                if (option.selected) {
                    selectedIndex = index;
                    return true;
                }
            });
            this.state = {
                selectedValue: this.props.options[selectedIndex].value,
            };
        }
    }

    public render() {
        if (!this.hasOptions) {
            return null;
        } else {
            const radioOptions = this.props.options.forEach((option, index) => {
                return (
                    <label className="dropDownRadio-option">
                        <input
                            type="radio"
                            className="dropDownRadio-input"
                            name={this.props.groupID}
                            value={option.value}
                            checked={this.state.selectedValue === option.value}
                            onChange={this.props.onChange}
                        />
                        <span className="dropDownRadio-check" aria-hidden={true}>
                            {option.selected && <CheckIcon />}
                        </span>
                        <span className="dropDownRadio-label">{option.name}</span>
                    </label>
                );
            });

            return (
                <DropDownItem className={classNames("dropDown-radioItem", this.props.className)}>
                    <fieldset className="dropDownRadio">
                        <legend className="dropDownRadio-title">{this.props.name}</legend>
                        {radioOptions}
                    </fieldset>
                </DropDownItem>
            );
        }
    }

    private onChange = e => {
        this.setState({
            selectedValue: e.currentTarget.value,
        });
    };
}
