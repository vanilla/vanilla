/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Select from "react-select";
import CreatableSelect from "react-select/lib/Creatable";
import { uniqueIDFromPrefix, getRequiredID, IOptionalComponentID } from "@library/componentIDs";
import classNames from "classnames";
import { t } from "@library/application";
import Button from "@library/components/forms/Button";
import Heading from "@library/components/Heading";
import { clearIndicator } from "@library/components/forms/select/overwrites/clearIndicator";
import menuList from "@library/components/forms/select/overwrites/menuList";
import menu from "@library/components/forms/select/overwrites/menu";
import selectContainer from "@library/components/forms/select/overwrites/selectContainer";
import doNotRender from "@library/components/forms/select/overwrites/doNotRender";
import Paragraph from "@library/components/Paragraph";
import { IComboBoxOption } from "./BigSearch";
import SelectOption from "./overwrites/SelectOption";

interface IProps extends IOptionalComponentID {
    label: string;
    disabled?: boolean;
    className?: string;
    options: IComboBoxOption[];
    setOption: (option: IComboBoxOption[]) => void;
}

/**
 * Implements the search bar component
 */
export default class SelectOne extends React.Component<IProps> {
    private id: string;
    private prefix = "selectOne";
    private inputID: string;

    constructor(props: IProps) {
        super(props);
        this.id = getRequiredID(props, this.prefix);
        // this.searchButtonID = this.id + "-searchButton";
        this.inputID = this.id + "-selectOneInput";
    }

    /**
     * Change handler for date within
     */
    private handleOnChange = (newValue: any, actionMeta: any) => {
        this.props.setOption(newValue);
    };

    public render() {
        const { className, disabled, options } = this.props;

        /** The children to be rendered inside the indicator. */
        const componentOverwrites = {
            IndicatorsContainer: doNotRender,
            SelectContainer: selectContainer,
            Menu: menu,
            MenuList: menuList,
            Option: SelectOption,
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
            <div className={this.props.className}>
                <label htmlFor={this.inputID} className="inputBlock-labelAndDescription">
                    <span className="inputBlock-labelText">{this.props.label}</span>
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
                        aria-label={this.props.label}
                        escapeClearsValue={true}
                        pageSize={20}
                        theme={getTheme}
                        styles={customStyles}
                        backspaceRemovesValue={true}
                    />
                </div>
            </div>
        );
    }
}
