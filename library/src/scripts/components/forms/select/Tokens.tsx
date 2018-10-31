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
import selectOption from "@library/components/forms/select/overwrites/selectOption";
import valueContainerTokens from "@library/components/forms/select/overwrites/valueContainerTokens";
import multiValueContainer from "./overwrites/multiValueContainer";
import multiValueLabel from "./overwrites/multiValueLabel";
import multiValueRemove from "./overwrites/multiValueRemove";
import noOptionsMessage from "./overwrites/noOptionsMessage";
import { IComboBoxOption } from "./SearchBar";

interface IProps extends IOptionalComponentID {
    label: string;
    labelNote?: string;
    disabled?: boolean;
    className?: string;
    placeholder?: string;
    options: IComboBoxOption[];
    setAuthor: (authors: IComboBoxOption[]) => void;
}

/**
 * Implements the search bar component
 */
export default class Tokens extends React.Component<IProps> {
    private id: string;
    private prefix = "tokens";
    private inputID: string;

    constructor(props: IProps) {
        super(props);
        this.id = getRequiredID(props, this.prefix);
        // this.searchButtonID = this.id + "-searchButton";
        this.inputID = this.id + "-tokenInput";
    }

    private handleOnChange = (newValue: any, actionMeta: any) => {
        this.props.setAuthor(newValue);
    };

    public render() {
        const { className, disabled, options } = this.props;

        return (
            <div className={classNames("tokens", "inputBlock", this.props.className)}>
                <label htmlFor={this.inputID} className="inputBlock-labelAndDescription">
                    <span className="inputBlock-labelText">{this.props.label}</span>
                    <Paragraph className="inputBlock-labelNote" children={this.props.labelNote} />
                </label>

                <div className="inputBlock-inputWrap">
                    <Select
                        id={this.id}
                        inputId={this.inputID}
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
                        styles={this.getStyles()}
                        backspaceRemovesValue={true}
                        isMulti={true}
                    />
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
        Option: selectOption,
        ValueContainer: valueContainerTokens,
        Control: multiValueContainer,
        MultiValueContainer: multiValueContainer,
        MultiValueLabel: multiValueLabel,
        MultiValueRemove: multiValueRemove,
        NoOptionsMessage: noOptionsMessage,
    };

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
            option: () => ({}),
            menu: base => {
                return { ...base, backgroundColor: null, boxShadow: null };
            },
            control: () => ({
                borderWidth: 0,
            }),
            multiValue: base => {
                return { ...base, borderRadius: null };
            },
            multiValueLabel: base => {
                return { ...base, borderRadius: null };
            },
        };
    };
}
