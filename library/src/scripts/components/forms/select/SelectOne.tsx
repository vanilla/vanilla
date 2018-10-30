/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Select from "react-select";
import { getRequiredID, IOptionalComponentID } from "@library/componentIDs";
import classNames from "classnames";
import { t } from "@library/application";
import menuList from "@library/components/forms/select/overwrites/menuList";
import menu from "@library/components/forms/select/overwrites/menu";
import selectContainer from "@library/components/forms/select/overwrites/selectContainer";
import doNotRender from "@library/components/forms/select/overwrites/doNotRender";
import Paragraph from "@library/components/Paragraph";
import { IComboBoxOption } from "./BigSearch";
import SelectOption from "@library/components/forms/select/overwrites/selectOption";
import { IFieldError } from "@library/@types/api";
import ErrorMessages from "@library/components/forms/ErrorMessages";
import valueContainer from "@library/components/forms/select/overwrites/valueContainer";
import controlContainer from "@library/components/forms/select/overwrites/controlContainer";
import noOptionsMessage from "./overwrites/noOptionsMessage";

interface IProps extends IOptionalComponentID {
    label: string;
    disabled?: boolean;
    className?: string;
    placeholder?: string;
    options: IComboBoxOption[];
    setData: (data: any) => void;
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

    private handleOnChange = (newValue: any, actionMeta: any) => {
        this.props.setData(newValue);
    };

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
        IndicatorsContainer: doNotRender,
        SelectContainer: selectContainer,
        Menu: menu,
        MenuList: menuList,
        Option: SelectOption,
        ValueContainer: valueContainer,
        Control: controlContainer,
        NoOptionsMessage: noOptionsMessage,
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
