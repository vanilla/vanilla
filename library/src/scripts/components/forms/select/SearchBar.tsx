/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { components } from "react-select";
import CreatableSelect from "react-select/lib/Creatable";
import { getRequiredID, IOptionalComponentID } from "@library/componentIDs";
import classNames from "classnames";
import { t } from "@library/application";
import Button from "@library/components/forms/Button";
import Heading from "@library/components/Heading";
import { InputActionMeta } from "react-select/lib/types";
import * as selectOverrides from "./overwrites";

export interface IComboBoxOption {
    value: string | number;
    label: string;
    data?: any;
}

interface IProps extends IOptionalComponentID {
    disabled?: boolean;
    className?: string;
    placeholder: string;
    options?: any[];
    loadOptions?: any[];
    value: string;
    onChange: (value) => void;
    isBigInput?: boolean;
    noHeading: boolean;
    title: React.ReactNode;
}

/**
 * Implements the search bar component
 */
export default class BigSearch extends React.Component<IProps> {
    public static defaultProps = {
        disabled: false,
        isBigInput: false,
        noHeading: false,
        title: t("Search"),
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
        const { className, disabled, options, value } = this.props;

        return (
            <CreatableSelect
                onChange={this.props.onChange}
                value={value}
                id={this.id}
                inputId={this.searchInputID}
                inputValue={this.props.value}
                onInputChange={this.handleInputChange}
                components={this.componentOverwrites}
                isClearable={true}
                isDisabled={disabled}
                options={options}
                classNamePrefix={this.prefix}
                className={classNames(this.prefix, className)}
                placeholder={this.props.placeholder}
                aria-label={t("Search")}
                escapeClearsValue={true}
                pageSize={20}
                theme={this.getTheme}
                styles={this.customStyles}
                backspaceRemovesValue={true}
            />
        );
    }

    private handleInputChange = (value: string, reason: InputActionMeta) => {
        if (!["input-blur", "menu-close"].includes(reason.action)) {
            this.props.onChange(value);
        }
    };

    private customStyles = {
        option: () => ({}),
        menu: base => {
            return { ...base, backgroundColor: null, boxShadow: null };
        },
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
     * Note that this is NOT a real react component and it needs to be defined here because we need to access the props from the plugin
     * @param props
     */
    private searchControl = props => {
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
                        {t("Search")}
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
        Control: this.searchControl,
        IndicatorSeparator: selectOverrides.NullComponent,
        DropdownIndicator: selectOverrides.NullComponent,
        ClearIndicator: selectOverrides.ClearIndicator,
        Menu: selectOverrides.Menu,
        MenuList: selectOverrides.MenuList,
        Option: selectOverrides.SearchResultOption,
        NoOptionsMessage: selectOverrides.NoOptionsMessage,
    };
}
