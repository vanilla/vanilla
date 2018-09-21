/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { check } from "../../Icons";
import DropDownItem from "@library/components/dropdown/items/DropDownItem";

interface IOption {
    name: string;
    value: string;
    selected?: boolean;
}

interface IProps {
    name: string;
    groupID: string; // the "name" of the radio button group
    options: IOption[];
    onChange: () => void;
}

export interface IState {
    selectedValue: string;
}

export default class DropDownItemRadio extends React.Component<IProps, IState> {
    private hasOptions: boolean;

    public constructor(props) {
        super(props);
        this.hasOptions = props.options.length > 0;

        if (!props.selectedOption && this.hasOptions) {
            props.selectedOption = props.options[0].value;
        }

        let selectedIndex = 0;

        props.selectedOption.some( (option, index) => {
            if (option.selected) {
                selectedIndex = index;
                return true;
            }
        });

        this.state = {
            selectedValue: this.props.options[selectedIndex].value,
        };
    }

    public render() {
        const radioOptions = this.props.options.map((option, index) => {
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
                        {option.selected && check()}
                    </span>
                    <span className="dropDownRadio-label">
                        {option.name}
                    </span>
                </label>
            );
        });

        if (!this.hasOptions) {
            return null;
        } else {
            return (
                <DropDownItem>
                    <fieldset className="dropDownRadio">
                        <legend className="dropDownRadio-title">
                            {this.props.name}
                        </legend>
                        {radioOptions}
                    </fieldset>
                </DropDownItem>
            );
        }
    }

    private onChange = (e) => {
        this.setState({
            selectedValue: e.currentTarget.value,
        });
    };
}
