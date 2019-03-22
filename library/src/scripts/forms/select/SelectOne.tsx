/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IFieldError } from "@library/@types/api/core";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import ErrorMessages from "@library/forms/ErrorMessages";
import * as selectOverrides from "@library/forms/select/overwrites";
import Paragraph from "@library/layout/Paragraph";
import { getRequiredID, IOptionalComponentID } from "@library/utility/idUtils";
import classNames from "classnames";
import React from "react";
import Select from "react-select";
import { OptionProps } from "react-select/lib/components/Option";
import { styleFactory } from "@library/styles/styleUtils";
import { style } from "typestyle";

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
    noOptionsMessage?: ((props: OptionProps<any>) => JSX.Element | null);
    isLoading?: boolean;
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

        const inputWrapClass = style({
            $nest: {
                ".inputBlock-inputText": {
                    paddingRight: 30,
                    position: "relative",
                },
                ".SelectOne__indicators": {
                    position: "absolute",
                    top: 0,
                    right: 6,
                    bottom: 0,
                },
                ".SelectOne__indicator": {
                    cursor: "pointer",
                },
            },
        });
        return (
            <div className={this.props.className}>
                <label htmlFor={this.inputID} className="inputBlock-labelAndDescription">
                    <span className={classNames("inputBlock-labelText", this.props.label)}>{this.props.label}</span>
                    <Paragraph className="inputBlock-labelNote" children={this.props.labelNote} />
                </label>

                <div className={classNames("inputBlock-inputWrap", inputWrapClass)}>
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
                        placeholder={this.props.placeholder}
                        isLoading={this.props.isLoading}
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
        Menu: selectOverrides.Menu,
        MenuList: selectOverrides.MenuList,
        Option: selectOverrides.SelectOption,
        ValueContainer: selectOverrides.ValueContainer,
        NoOptionsMessage: this.props.noOptionsMessage || selectOverrides.NoOptionsMessage,
        LoadingMessage: selectOverrides.OptionLoader,
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
