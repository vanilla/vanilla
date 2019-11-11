/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IFieldError } from "@library/@types/api/core";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import ErrorMessages from "@library/forms/ErrorMessages";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import * as selectOverrides from "@library/forms/select/overwrites";
import { selectOneClasses } from "@library/forms/select/selectOneStyles";
import Paragraph from "@library/layout/Paragraph";
import { IOptionalComponentID, useUniqueID } from "@library/utility/idUtils";
import classNames from "classnames";
import React, { useCallback, useMemo, useState } from "react";
import Select from "react-select";
import { OptionProps } from "react-select/lib/components/Option";

export interface ISelectOneProps {
    label: string | null;
    id?: string;
    inputID?: string;
    labelID?: string;
    disabled?: boolean;
    className?: string;
    placeholder?: string;
    options: IComboBoxOption[] | undefined;
    onChange: (data: IComboBoxOption) => void;
    onInputChange?: (value: string) => void;
    labelNote?: string;
    noteAfterInput?: string;
    errors?: IFieldError[];
    searchable?: boolean;
    value: IComboBoxOption | undefined;
    noOptionsMessage?: (props: OptionProps<any>) => JSX.Element | null;
    isLoading?: boolean;
    inputClassName?: string;
    isClearable?: boolean;
    describedBy?: string;
}

/**
 * Implements the search bar component
 */
export default function SelectOne(props: ISelectOneProps) {
    // Overwrite components in Select component
    const overrideProps = useOverrideProps(props);

    const prefix = "SelectOne";
    const [isFocused, setIsFocused] = useState(false);
    const generatedID = useUniqueID(prefix);
    const id = props.id || generatedID;
    const inputID = props.inputID || id + "-input";
    const errorID = id + "-errors";

    const { className, disabled, options, searchable } = props;
    let describedBy;
    const hasErrors = props.errors && props.errors!.length > 0;
    if (hasErrors) {
        describedBy = errorID;
    }

    const classes = selectOneClasses();
    const classesInputBlock = inputBlockClasses();
    return (
        <div className={classNames(classesInputBlock.root, props.className)}>
            {props.label !== null && (
                <label htmlFor={inputID} className={classesInputBlock.labelAndDescription}>
                    <span className={classNames(classesInputBlock.labelText, props.label)}>{props.label}</span>
                    <Paragraph className={classesInputBlock.labelNote}>{props.labelNote}</Paragraph>
                </label>
            )}

            <div className={classNames(classesInputBlock.inputWrap, classes.inputWrap, { hasFocus: isFocused })}>
                <Select
                    {...overrideProps}
                    id={id}
                    options={options}
                    inputId={inputID}
                    onChange={props.onChange}
                    onInputChange={props.onInputChange}
                    isClearable={props.isClearable}
                    isDisabled={disabled}
                    classNamePrefix={prefix}
                    className={classNames(prefix, className)}
                    aria-label={props.label || undefined}
                    aria-labelledby={props.labelID || undefined}
                    aria-invalid={hasErrors}
                    aria-describedby={describedBy}
                    isSearchable={searchable}
                    value={props.value}
                    menuIsOpen={isFocused === false ? false : undefined}
                    placeholder={props.placeholder}
                    isLoading={props.isLoading}
                    onFocus={() => setIsFocused(true)}
                    onBlur={() => setIsFocused(false)}
                    menuPlacement="auto"
                />
                <Paragraph className={classesInputBlock.labelNote}>{props.noteAfterInput}</Paragraph>
                <ErrorMessages id={errorID} errors={props.errors} />
            </div>
        </div>
    );
}

SelectOne.defaultProps = {
    isClearable: true,
};

/**
 * Hook to create react-select override props.
 */
function useOverrideProps(props: ISelectOneProps) {
    const { inputClassName, noOptionsMessage } = props;
    const componentOverwrites = useMemo(() => {
        return {
            Menu: selectOverrides.Menu,
            MenuList: selectOverrides.MenuList,
            Option: selectOverrides.SelectOption,
            ValueContainer: function CustomValueContainer(localProps) {
                return <selectOverrides.ValueContainer {...localProps} className={inputClassName} />;
            },
            NoOptionsMessage: noOptionsMessage || selectOverrides.NoOptionsMessage,
            LoadingMessage: selectOverrides.OptionLoader,
        };
    }, [inputClassName, noOptionsMessage]);

    const customStyles = useMemo(() => {
        return {
            option: () => ({}),
            menu: base => {
                return { ...base, backgroundColor: null, boxShadow: null };
            },
            control: () => ({
                borderWidth: 0,
            }),
        };
    }, []);

    // Overwrite theme in Select component
    const getTheme = useCallback(theme => {
        return {
            ...theme,
            borderRadius: {},
            borderWidth: 0,
            colors: {},
            spacing: {},
        };
    }, []);

    return {
        components: componentOverwrites,
        theme: getTheme,
        styles: customStyles,
    };
}
