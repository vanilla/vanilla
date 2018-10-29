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
import selectOption from "@library/components/forms/select/overwrites/SelectOption";
import valueContainer from "@library/components/forms/select/overwrites/valueContainer";
import controlContainer from "@library/components/forms/select/overwrites/controlContainer";

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
        console.log("New value: ", newValue);
        // this.props.setAuthor(newValue);
    };

    public render() {
        const { className, disabled, options } = this.props;

        /** The children to be rendered inside the indicator. */
        const componentOverwrites = {
            IndicatorsContainer: doNotRender,
            SelectContainer: selectContainer,
            Menu: menu,
            MenuList: menuList,
            Option: selectOption,
            ValueContainer: valueContainer,
            Control: controlContainer,
        };

        const getTheme = theme => {
            return {
                ...theme,
                border: {},
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

        return (
            <div className={this.props.className}>
                <label htmlFor={this.inputID} className="inputBlock-labelAndDescription">
                    <span className="inputBlock-labelText">{this.props.label}</span>
                    <Paragraph className="inputBlock-labelNote" children={this.props.labelNote} />
                </label>

                <div className="inputBlock-inputWrap">
                    <Select
                        id={this.id}
                        inputId={this.inputID}
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
                        backspaceRemovesValue={true}
                        isMulti={true}
                    />
                </div>
            </div>
        );
    }
}
