/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { components } from "react-select";
import AsyncCreatableSelect from "react-select/lib/AsyncCreatable";
import { getRequiredID, IOptionalComponentID } from "@library/componentIDs";
import classNames from "classnames";
import { t } from "@library/application";
import Button from "@library/components/forms/Button";
import Heading from "@library/components/Heading";
import { InputActionMeta } from "react-select/lib/types";
import * as selectOverrides from "./overwrites";
import ButtonLoader from "@library/components/ButtonLoader";
import { OptionProps } from "react-select/lib/components/Option";
import { ISearchResult } from "@knowledge/@types/api";
import Translate from "@library/components/translation/Translate";

export interface IComboBoxOption<T = any> {
    value: string | number;
    label: string;
    data?: T;
}

interface IProps extends IOptionalComponentID {
    disabled?: boolean;
    className?: string;
    placeholder: string;
    options?: any[];
    loadOptions?: (inputValue: string) => Promise<any>;
    optionComponent: React.ComponentType;
    value: string;
    onChange: (value: string) => void;
    isBigInput?: boolean;
    noHeading: boolean;
    title: React.ReactNode;
    isLoading: boolean;
    onSearch: () => void;
}

interface IState {
    forceMenuClosed: boolean;
}

/**
 * Implements the search bar component
 */
export default class BigSearch extends React.Component<IProps, IState> {
    public static defaultProps = {
        disabled: false,
        isBigInput: false,
        noHeading: false,
        title: t("Search"),
    };

    public state: IState = {
        forceMenuClosed: false,
    };
    private id: string;
    private prefix = "searchBar";
    private searchButtonID: string;
    private searchInputID: string;

    constructor(props: IProps) {
        super(props);
        this.id = getRequiredID(props, this.prefix);
        this.searchButtonID = this.id + "-searchButton";
        this.searchInputID = this.id + "-searchInput";
    }

    public render() {
        const { className, disabled, isLoading } = this.props;

        return (
            <AsyncCreatableSelect
                id={this.id}
                value={undefined}
                onChange={this.handleOptionChange}
                closeMenuOnSelect={false}
                inputId={this.searchInputID}
                inputValue={this.props.value}
                onInputChange={this.handleInputChange}
                components={this.componentOverwrites}
                isClearable={false}
                blurInputOnSelect={false}
                controlShouldRenderValue={false}
                isDisabled={disabled || isLoading}
                loadOptions={this.props.loadOptions}
                menuIsOpen={this.isMenuVisible}
                classNamePrefix={this.prefix}
                className={classNames(this.prefix, className)}
                placeholder={this.props.placeholder}
                aria-label={t("Search")}
                escapeClearsValue={true}
                pageSize={20}
                theme={this.getTheme}
                styles={this.customStyles}
                backspaceRemovesValue={true}
                createOptionPosition="first"
                formatCreateLabel={this.createLabel}
            />
        );
    }

    private get isMenuVisible(): boolean | undefined {
        return this.state.forceMenuClosed || this.props.value.length === 0 ? false : undefined;
    }

    private handleOptionChange = (option: IComboBoxOption) => {
        if (option) {
            this.props.onChange(option.label);
            this.setState({ forceMenuClosed: true }, () => {
                this.props.onSearch && this.props.onSearch();
            });
        }
    };

    private createLabel = (inputValue: string) => {
        return <Translate source="Search for <0/>" c0={<strong>{inputValue}</strong>} />;
    };

    private handleInputChange = (value: string, reason: InputActionMeta) => {
        if (!["input-blur", "menu-close"].includes(reason.action)) {
            this.props.onChange(value);
            this.setState({ forceMenuClosed: false });
        }
    };

    private customStyles = {
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
    };

    private getTheme = theme => {
        return {
            ...theme,
            borderRadius: {},
            colors: {},
            spacing: {},
        };
    };

    /**
     * Overwrite for the Control component in react select
     * @param props
     */
    private SearchControl = props => {
        return (
            <form className="searchBar-form" onSubmit={this.preventFormSubmission}>
                {!this.props.noHeading && (
                    <Heading depth={1} className="searchBar-heading">
                        <label className="searchBar-label" htmlFor={this.searchInputID}>
                            {this.props.title}
                        </label>
                    </Heading>
                )}
                <div className="searchBar-content">
                    <div
                        className={classNames(
                            `${this.prefix}-valueContainer`,
                            "suggestedTextInput-inputText",
                            "inputText",
                            "isClearable",
                            {
                                isLarge: this.props.isBigInput,
                            },
                        )}
                    >
                        <components.Control {...props} />
                    </div>
                    <Button type="submit" id={this.searchButtonID} className="buttonPrimary searchBar-submitButton">
                        {this.props.isLoading ? <ButtonLoader /> : t("Search")}
                    </Button>
                </div>
            </form>
        );
    };

    private preventFormSubmission = e => {
        e.preventDefault();
    };

    /*
    * Overwrite components in Select component
    */
    private componentOverwrites = {
        Control: this.SearchControl,
        IndicatorSeparator: selectOverrides.NullComponent,
        Menu: selectOverrides.Menu,
        MenuList: selectOverrides.MenuList,
        Option: selectOverrides.SelectOption,
        NoOptionsMessage: selectOverrides.NoOptionsMessage,
        ClearIndicator: selectOverrides.NullComponent,
        DropdownIndicator: selectOverrides.NullComponent,
        LoadingMessage: selectOverrides.OptionLoader,
    };
}
