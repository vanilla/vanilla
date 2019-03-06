/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Select from "react-select";
import { getRequiredID, IOptionalComponentID } from "@library/componentIDs";
import classNames from "classnames";
import { t } from "@library/application";
import Paragraph from "@library/components/Paragraph";
import * as selectOverrides from "./overwrites";
import { IComboBoxOption } from "./SearchBar";
import { tokensClasses } from "@library/styles/tokensStyles";

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
    };

    public render() {
        const { className, disabled, options, isLoading } = this.props;
        const classes = tokensClasses();

        return (
            <div className={classNames("tokens", "inputBlock", this.props.className, classes.root)}>
                <label htmlFor={this.inputID} className="inputBlock-labelAndDescription">
                    <span className="inputBlock-labelText">{this.props.label}</span>
                    <Paragraph className="inputBlock-labelNote" children={this.props.labelNote} />
                </label>

                <div className="inputBlock-inputWrap">
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
