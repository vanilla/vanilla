/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Select from "react-select";
import { getRequiredID, IOptionalComponentID } from "@library/componentIDs";
import classNames from "classnames";
import Paragraph from "@library/components/Paragraph";
import { IFieldError } from "@library/@types/api";
import ErrorMessages from "@library/components/forms/ErrorMessages";
import * as selectOverrides from "./overwrites";
import { IComboBoxOption } from "./SearchBar";

interface IProps extends IOptionalComponentID {
    label: string;
    disabled?: boolean;
    className?: string;
    placeholder?: string;
    options: IComboBoxOption[];
    onChange: (data: IComboBoxOption) => void;
    labelNote?: string;
    noteAfterInput?: string;
    errors?: IFieldError[];
    searchable?: boolean;
}

/**
 * Implements the search bar component
 */
export default class SelectOne extends React.Component<IProps> {
    private id: string;
    private prefix = "SelectOne";
    private inputID: string;
    private errorID: string;

    constructor(props: IProps) {
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
