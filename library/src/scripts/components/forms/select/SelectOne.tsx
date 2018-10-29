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
import SelectOption from "@library/components/forms/select/overwrites/SelectOption";
import { IFieldError } from "@library/@types/api";
import ErrorMessages from "@dashboard/components/forms/ErrorMessages";
import valueContainer from "@library/components/forms/select/overwrites/valueContainer";
import controlContainer from "@library/components/forms/select/overwrites/controlContainer";

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

        /** The children to be rendered inside the indicator. */
        const componentOverwrites = {
            IndicatorsContainer: doNotRender,
            SelectContainer: selectContainer,
            Menu: menu,
            MenuList: menuList,
            Option: SelectOption,
            ValueContainer: valueContainer,
            Control: controlContainer,
        };

        const getTheme = theme => {
            return {
                ...theme,
                borderRadius: {},
                borderWidth: 0,
                colors: {},
                spacing: {},
            };
        };

        const customStyles = {
            option: () => ({}),
            menu: base => {
                return { ...base, backgroundColor: null, boxShadow: null };
            },
            control: () => ({
                borderWidth: 0,
            }),
        };

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
                        components={componentOverwrites}
                        isClearable={true}
                        isDisabled={disabled}
                        classNamePrefix={this.prefix}
                        className={classNames(this.prefix, className)}
                        aria-label={this.props.label}
                        theme={getTheme}
                        styles={customStyles}
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
}
