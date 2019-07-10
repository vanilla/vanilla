/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { tokensClasses } from "@library/forms/select/tokensStyles";
import { t } from "@library/utility/appUtils";
import { getRequiredID, IOptionalComponentID } from "@library/utility/idUtils";
import Select from "react-select";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import Paragraph from "@library/layout/Paragraph";
import classNames from "classnames";
import * as selectOverrides from "@library/forms/select/overwrites";

interface IProps extends IOptionalComponentID {
    label: string;
    labelNote?: string;
    disabled?: boolean;
    className?: string;
    placeholder?: string;
    options: IComboBoxOption[] | undefined;
    isLoading?: boolean;
    value: IComboBoxOption[];
    onChange: (tokens: IComboBoxOption[]) => void;
    onInputChange: (value: string) => void;
}

interface IState {
    inputValue: string;
    focus: boolean;
}

/**
 * Implements the search bar component
 */
export default class Tokens extends React.Component<IProps, IState> {
    private prefix = "tokens";
    private id: string = getRequiredID(this.props, this.prefix);
    private inputID: string = this.id + "-tokenInput";
    public state: IState = {
        inputValue: "",
        focus: false,
    };

    public render() {
        const { className, disabled, options, isLoading } = this.props;
        const classes = tokensClasses();

        return (
            <div className={classNames("tokens", "inputBlock", this.props.className, classes.root)}>
                <label htmlFor={this.inputID} className="inputBlock-labelAndDescription">
                    <span className="inputBlock-labelText">{this.props.label}</span>
                    <Paragraph className="inputBlock-labelNote">{this.props.labelNote}</Paragraph>
                </label>

                <div className={classNames("inputBlock-inputWrap", classes.inputWrap, { hasFocus: this.state.focus })}>
                    <Select
                        id={this.id}
                        inputId={this.inputID}
                        components={this.componentOverwrites}
                        onChange={this.props.onChange}
                        inputValue={this.state.inputValue}
                        value={this.props.value}
                        onInputChange={this.handleInputChange}
                        isClearable={true}
                        isDisabled={disabled}
                        options={options}
                        isLoading={this.showLoader}
                        classNamePrefix={this.prefix}
                        className={classNames(this.prefix, className)}
                        placeholder={this.props.placeholder}
                        aria-label={t("Search")}
                        escapeClearsValue={true}
                        pageSize={20}
                        theme={this.getTheme}
                        styles={this.getStyles()}
                        backspaceRemovesValue={true}
                        isMulti={true}
                        onFocus={this.onFocus}
                        onBlur={this.onBlur}
                    />
                </div>
            </div>
        );
    }

    private get showLoader(): boolean {
        return !!this.props.isLoading && this.state.inputValue.length > 0;
    }

    private handleInputChange = val => {
        this.setState({ inputValue: val });
        this.props.onInputChange(val);
    };

    /*
     * Overwrite components in Select component
     */
    private get componentOverwrites() {
        return {
            ClearIndicator: selectOverrides.NullComponent,
            DropdownIndicator: selectOverrides.NullComponent,
            LoadingMessage: selectOverrides.OptionLoader,
            Menu: this.state.inputValue.length > 0 ? selectOverrides.Menu : selectOverrides.NullComponent,
            MenuList: selectOverrides.MenuList,
            Option: selectOverrides.SelectOption,
            ValueContainer: selectOverrides.ValueContainer,
            Control: selectOverrides.Control,
            MultiValueRemove: selectOverrides.MultiValueRemove,
            NoOptionsMessage: this.showLoader
                ? selectOverrides.OptionLoader
                : this.state.inputValue.length > 0
                ? selectOverrides.NoOptionsMessage
                : selectOverrides.NullComponent,
            LoadingIndicator: selectOverrides.NullComponent,
        };
    }

    /**
     * Overwrite theme in Select component
     */
    private getTheme = theme => {
        return {
            ...theme,
            border: {},
            colors: {},
            spacing: {},
        };
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
     * Overwrite styles in Select component
     */
    private getStyles = () => {
        return {
            option: (provided: React.CSSProperties) => ({
                ...provided,
            }),
            menu: (provided: React.CSSProperties, state) => {
                return { ...provided, backgroundColor: undefined, boxShadow: undefined };
            },
            control: (provided: React.CSSProperties) => ({
                ...provided,
                borderWidth: 0,
            }),
            multiValue: (provided: React.CSSProperties, state) => {
                return {
                    ...provided,
                    borderRadius: undefined,
                    opacity: state.isFocused ? 1 : 0.85,
                };
            },
            multiValueLabel: (provided: React.CSSProperties) => {
                return { ...provided, borderRadius: undefined, padding: 0 };
            },
        };
    };
}
