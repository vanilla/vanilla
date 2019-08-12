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
import { calc } from "csx";
import { colorOut, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";

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
    noOptionsMessage?: (props: OptionProps<any>) => JSX.Element | null;
    isLoading?: boolean;
}

interface IState {
    focus: boolean;
}

/**
 * Implements the search bar component
 */
export default class SelectOne extends React.Component<ISelectOneProps, IState> {
    private id: string;
    private prefix = "SelectOne";
    private inputID: string;
    private errorID: string;
    private focus: boolean;

    constructor(props: ISelectOneProps) {
        super(props);
        this.id = getRequiredID(props, this.prefix);
        this.inputID = this.id + "-input";
        this.errorID = this.id + "-errors";
        this.focus = false;
        this.state = {
            focus: false,
        };
    }

    public render() {
        const { className, disabled, options, searchable } = this.props;
        const style = styleFactory("SelectOne");
        let describedBy;
        const hasErrors = this.props.errors && this.props.errors!.length > 0;
        if (hasErrors) {
            describedBy = this.errorID;
        }

        const rightPadding = 30;
        const inputWrapClass = style("inputWrarp", {
            $nest: {
                "&.hasFocus .inputBlock-inputText": {
                    borderColor: colorOut(globalVariables().mainColors.primary),
                },
                ".inputBlock-inputText": {
                    paddingRight: unit(rightPadding),
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
                "& .SelectOne__single-value": {
                    textOverflow: "ellipsis",
                    maxWidth: calc(`100% - ${unit(rightPadding + 26)}`),
                },
            },
        });
        const classesInputBlock = inputBlockClasses();
        return (
            <div className={this.props.className}>
                <label htmlFor={this.inputID} className={classesInputBlock.labelAndDescription}>
                    <span className={classNames(classesInputBlock.labelText, this.props.label)}>
                        {this.props.label}
                    </span>
                    <Paragraph className={classesInputBlock.labelNote}>{this.props.labelNote}</Paragraph>
                </label>

                <div
                    className={classNames(classesInputBlock.inputWrap, inputWrapClass, { hasFocus: this.state.focus })}
                >
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
                        onFocus={this.onFocus}
                        onBlur={this.onBlur}
                    />
                    <Paragraph className={classesInputBlock.labelNote}>{this.props.noteAfterInput}</Paragraph>
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
     * Set class for focus
     */
    private onFocus = () => {
        this.setState({
            focus: true,
        });
    };

    /**
     * Set class for blur
     */
    private onBlur = () => {
        this.setState({
            focus: false,
        });
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
