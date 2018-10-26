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
        this.props.setQuery(newValue);
    };

    private handleInputChange = (newValue: any, actionMeta: any) => {
        this.props.setQuery({ data: newValue });
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
            // ValueContainer,
        };

        const getTheme = theme => {
            return {
                ...theme,
                borderRadius: {},
                color: {},
                spacing: {},
            };
        };

        return (
            <CreatableSelect
                id={this.id}
                components={componentOverwrites}
                isClearable={true}
                isDisabled={disabled}
                options={options}
                classNamePrefix={this.prefix}
                className={classNames(this.prefix, className)}
                styles={{}}
                placeholder={this.props.placeholder}
                value={this.props.query}
                aria-label={t("Search")}
                escapeClearsValue={true}
                inputId={this.searchInputID}
                pageSize={20}
                theme={getTheme}
                onChange={this.handleOnChange}
                onInputChange={this.handleInputChange}
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
                    <div className={`${props.prefix}-valueContainer inputBlock-inputText InputBox inputText isLarge`}>
                        <components.Control {...props} />
                    </div>
                    <Button type="submit" id={this.searchButtonID} className="buttonPrimary bigSearch-submitButton">
                        {t("Search")}
                    </Button>
                </div>
            </form>
        );
    };

    //option
}

// Wrap in form if not already
// Role search on input
