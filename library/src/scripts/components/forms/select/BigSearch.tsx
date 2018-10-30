/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { components } from "react-select";
import CreatableSelect from "react-select/lib/Creatable";
import { uniqueIDFromPrefix, getRequiredID, IOptionalComponentID } from "@library/componentIDs";
import classNames from "classnames";
import { t } from "@library/application";
import Button from "@library/components/forms/Button";
import Heading from "@library/components/Heading";
import { clearIndicator } from "@library/components/forms/select/overwrites/clearIndicator";
import SearchResultOption from "@library/components/forms/select/overwrites/searchResultOption";
import menuList from "@library/components/forms/select/overwrites/menuList";
import menu from "@library/components/forms/select/overwrites/menu";
import selectContainer from "@library/components/forms/select/overwrites/selectContainer";
import doNotRender from "@library/components/forms/select/overwrites/doNotRender";
import noOptionsMessage from "./overwrites/noOptionsMessage";

export interface IComboBoxOption {
    value: string;
    label: string;
    data: any;
}

interface IProps extends IOptionalComponentID {
    query?: string;
    disabled?: boolean;
    className?: string;
    placeholder: string;
    options?: any[];
    loadOptions?: any[];
    setQuery: (value) => void;
}

interface IState {
    value: IComboBoxOption;
}

/**
 * Implements the search bar component
 */
export default class BigSearch extends React.Component<IProps> {
    public static defaultProps = {
        disabled: false,
    };

    private id: string;
    private prefix = "bigSearch";
    private searchButtonID: string;
    private searchInputID: string;

    constructor(props: IProps) {
        super(props);
        this.id = getRequiredID(props, this.prefix);
        this.searchButtonID = this.id + "-searchButton";
        this.searchInputID = this.id + "-searchInput";
    }

    private handleOnChange = (newValue: any, actionMeta: any) => {
        this.props.setQuery(newValue.label || "");
    };

    private handleInputChange = (newValue: any, actionMeta: any) => {
        this.props.setQuery(newValue.label || "");
    };

    public render() {
        const { className, disabled, options } = this.props;
        const getTheme = theme => {
            return {
                ...theme,
                borderRadius: {},
                colors: {},
                spacing: {},
            };
        };

        const customStyles = {
            option: () => ({}),
            menu: base => {
                return { ...base, backgroundColor: null, boxShadow: null };
            },
        };

        return (
            <CreatableSelect
                id={this.id}
                inputId={this.searchInputID}
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
                theme={getTheme}
                styles={customStyles}
                backspaceRemovesValue={true}
            />
        );
    }

    public getValue = value => {
        return value;
    };

    public preventFormSubmission = e => {
        e.preventDefault();
    };

    /**
     * Overwrite for the Control component in react select
     * Note that this is NOT a real react component and it needs to be defined here because we need to access the props from the plugin
     * @param props
     */
    private bigSearchControl = props => {
        const id = uniqueIDFromPrefix("searchInputBlock");
        const labelID = id + "-label";

        return (
            <form className="bigSearch-form" onSubmit={this.preventFormSubmission}>
                <Heading depth={1} className="bigSearch-heading">
                    <label className="searchInputBlock-label" htmlFor={this.searchInputID}>
                        {t("Search")}
                    </label>
                </Heading>
                <div className="bigSearch-content">
                    <div className={`${this.prefix}-valueContainer inputText isLarge isClearable`}>
                        <components.Control {...props} />
                    </div>
                    <Button type="submit" id={this.searchButtonID} className="buttonPrimary bigSearch-submitButton">
                        {t("Search")}
                    </Button>
                </div>
            </form>
        );
    };

    /*
    * Overwrite components in Select component
    */
    private componentOverwrites = {
        Control: this.bigSearchControl,
        IndicatorSeparator: doNotRender,
        DropdownIndicator: doNotRender,
        ClearIndicator: clearIndicator,
        SelectContainer: selectContainer,
        Menu: menu,
        MenuList: menuList,
        Option: SearchResultOption,
        NoOptionsMessage: noOptionsMessage,
    };
}

// Role search on input
