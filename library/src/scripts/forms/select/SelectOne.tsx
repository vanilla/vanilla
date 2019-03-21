/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ErrorMessages from "@library/forms/ErrorMessages";
import { getRequiredID, IOptionalComponentID } from "@library/utility/idUtils";
import Select from "react-select";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import classNames from "classnames";
import Paragraph from "@library/layout/Paragraph";
import { IFieldError } from "@library/@types/api/core";
import * as selectOverrides from "@library/forms/select/overwrites";

export interface ISelectOneProps extends IOptionalComponentID {
    label: string;
    disabled?: boolean;
    className?: string;
    placeholder?: string;
    options: IComboBoxOption[] | undefined;
    onChange: (data: IComboBoxOption) => void;
    onInputChange?: (value: string) => void;
    labelNote?: string;
    noteAfterInput?: string;
    errors?: IFieldError[];
    searchable?: boolean;
    value: IComboBoxOption | undefined;
}

/**
 * Implements the search bar component
 */
export default class SelectOne extends React.Component<ISelectOneProps> {
    private id: string;
    private prefix = "SelectOne";
    private inputID: string;
    private errorID: string;

    constructor(props: ISelectOneProps) {
        super(props);
        this.id = getRequiredID(props, this.prefix);
        this.inputID = this.id + "-input";
        this.errorID = this.id + "-errors";
    }

    public render() {
        const { className, disabled, options, searchable } = this.props;
        let describedBy;
        const hasErrors = this.props.errors && this.props.errors!.length > 0;
        if (hasErrors) {
            describedBy = this.errorID;
        }

        return (
            <div className={this.props.className}>
                <label htmlFor={this.inputID} className="inputBlock-labelAndDescription">
                    <span className={classNames("inputBlock-labelText", this.props.label)}>{this.props.label}</span>
                    <Paragraph className="inputBlock-labelNote" children={this.props.labelNote} />
                </label>

                <div className="inputBlock-inputWrap">
                    <Select
                        id={this.id}
                        options={options}
                        inputId={this.inputID}
                        onChange={this.props.onChange}
                        onInputChange={this.props.onInputChange}
                        components={this.componentOverwrites}
                        isClearable={true}
                        isDisabled={disabled}
                        classNamePrefix={this.prefix}
                        className={classNames(this.prefix, className)}
                        aria-label={this.props.label}
                        theme={this.getTheme}
                        styles={this.getStyles()}
                        aria-invalid={hasErrors}
                        aria-describedby={describedBy}
                        isSearchable={searchable}
                        value={this.props.value}
                    />
                    <Paragraph className="inputBlock-labelNote" children={this.props.noteAfterInput} />
                    <ErrorMessages id={this.errorID} errors={this.props.errors} />
                </div>
            </div>
        );
    }

    /*
    * Overwrite components in Select component
    */
    private componentOverwrites = {
        IndicatorsContainer: selectOverrides.NullComponent,
        Menu: selectOverrides.Menu,
        MenuList: selectOverrides.MenuList,
        Option: selectOverrides.SelectOption,
        ValueContainer: selectOverrides.ValueContainer,
        NoOptionsMessage: selectOverrides.NoOptionsMessage,
    };

    /**
     * Overwrite theme in Select component
     */
    private getTheme = theme => {
        return {
            ...theme,
            borderRadius: {},
            borderWidth: 0,
            colors: {},
            spacing: {},
        };
    };
    /**
     * Overwrite styles in Select component
     */
    private getStyles = () => {
        return {
            option: () => ({}),
            menu: base => {
                return { ...base, backgroundColor: null, boxShadow: null };
            },
            control: () => ({
                borderWidth: 0,
            }),
        };
    };
}
