/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { NavLink } from "react-router-dom";
import DropDownItem from "@library/components/dropdown/DropDownItem";
import { check } from "@library/components/Icons";

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
    selectedOption: string;
}

export default class DropDownRadio extends React.Component<IProps> {
    private hasOptions: boolean;
    private selectedValue: string;

    public constructor(props) {
        super(props);
        this.hasOptions = props.options.length > 0;

        if (!props.selectedOption && this.hasOptions) {
            props.selectedOption = props.options[0].value;
        }

        this.state = {
            selectedIndex: 0,
        };
    }

    public render() {
        const radioOptions = this.props.options.map((option) => {
            return (
                <label className="dropDownRadio-option">
                    <input type="radio" className="dropDownRadio-input" name={this.props.groupID} value={option.value} />
                    <span className="dropDownRadio-check" aria-hidden={true}>
                        {option.selected && check()}
                    </span>
                    <span className="dropDownRadio-label">
                        {option.name}
                    </span>
                </label>
            );
        });

        if (this.props.options.length === 0) {
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
}
