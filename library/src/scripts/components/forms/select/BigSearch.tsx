/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Select, { components } from "react-select";
import CreatableSelect from "react-select/lib/Creatable";
import { getOptionalID, uniqueIDFromPrefix, getRequiredID } from "@library/componentIDs";
import classNames from "classnames";
import { t } from "@library/application";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { clear } from "@library/components/Icons";
import Heading from "@library/components/Heading";
import { ClearIndicator } from "@library/components/forms/select/overwrites/ClearIndicator";
import SelectContainer from "@library/components/forms/select/overwrites/SelectContainer";
import DoNotRender from "@library/components/forms/select/overwrites/DoNotRender";
import Menu from "@library/components/forms/select/overwrites/Menu";
import MenuList from "@library/components/forms/select/overwrites/MenuList";
import Option from "@library/components/forms/select/overwrites/Option";

export interface IComboBoxOption {
    value: string;
    label: string;
    data: any;
}

interface IProps {
    query: string;
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

    private id;
    private prefix = "bigSearch";
    private searchButtonID;
    private searchInputID;

    constructor(props) {
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
        const { className, disabled, options, loadOptions } = this.props;

        /** The children to be rendered inside the indicator. */
        const componentOverwrites = {
            Control: this.BigSearchControl,
            IndicatorSeparator: DoNotRender,
            DropdownIndicator: DoNotRender,
            ClearIndicator,
            SelectContainer,
            Menu,
            MenuList,
            Option,
        };

        const getTheme = theme => {
            return {
                ...theme,
                borderRadius: {},
                color: {},
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
                components={componentOverwrites}
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
            />
        );
    }

    public getValue = value => {
        return value;
    };

    private BigSearchControl = props => {
        const id = uniqueIDFromPrefix("searchInputBlock");
        const labelID = id + "-label";

        const preventFormSubmission = e => {
            e.preventDefault();
            this.props.setQuery(props.getValue());
        };

        return (
            <form className="bigSearch-form" onSubmit={preventFormSubmission}>
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
}

// Role search on input
