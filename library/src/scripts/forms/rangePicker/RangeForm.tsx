/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */
import React, { useEffect, useState } from "react";
import { AutoComplete, FormGroup, FormGroupInput, FormGroupLabel, NumberBox } from "@vanilla/ui";
import DayPicker from "react-day-picker";
import DatePickerNav from "@library/forms/rangePicker/DatePickerNav";
import { rangePickerClasses } from "./RangePicker.styles";
import { rangeFormClasses } from "./RangeForm.styles";
import "react-day-picker/lib/style.css";
import { cx } from "@emotion/css";
import { t } from "@vanilla/i18n";
import {
    DateAddSubtractOperation,
    DateAddSubtractUnit,
    DateOperation,
    IDateModifierPickerProps,
    IDateOperationPickerProps,
} from "@library/forms/rangePicker/types";
import { applyDateModifier } from "@library/forms/rangePicker/utils";

const UNIT_VALUES = {
    days: "Days",
    weeks: "Weeks",
    months: "Months",
    quarters: "Quarters",
    years: "Years",
} as const;

const DATE_VALUES = {
    fixed: "Fixed",
    today: "Today",
    startOfWeek: "Start of week",
    startOfMonth: "Start of month",
    endOfWeek: "End of week",
    endOfMonth: "End of month",
} as const;

const DIRECTION_VALUES = {
    add: "Plus",
    subtract: "Minus",
} as const;

const valueFromLabel = (map, value) => Object.keys(map).find((key) => map[key] === value);

/**
 * Renders the relative dates form
 */
const OperationPicker = (props: IDateOperationPickerProps) => {
    const { setOperation } = props;
    const operation = props.operation as DateAddSubtractOperation;
    const { type, amount, unit } = operation;
    const classes = rangeFormClasses();
    return (
        <>
            <FormGroup sideBySide compact>
                <FormGroupLabel>{t("+/-")}</FormGroupLabel>
                <FormGroupInput>
                    {(props) => (
                        <AutoComplete
                            size="small"
                            value={DIRECTION_VALUES[type]}
                            onChange={(direction) => {
                                setOperation({ ...operation, type: direction });
                            }}
                            options={Object.entries(DIRECTION_VALUES).map(([value, label]) => ({
                                label,
                                value,
                            }))}
                        />
                    )}
                </FormGroupInput>
            </FormGroup>
            <FormGroup sideBySide compact>
                <FormGroupLabel>{t("Amount")}</FormGroupLabel>
                <FormGroupInput>
                    {(props) => (
                        <NumberBox
                            {...props}
                            className={classes.input}
                            size="small"
                            name="Amount"
                            min={0}
                            onValueChange={(value) => {
                                if (!["add", "subtract"].includes(type)) {
                                    return;
                                }
                                setOperation({ ...operation, amount: parseInt(value) });
                            }}
                            value={amount}
                        />
                    )}
                </FormGroupInput>
            </FormGroup>
            <FormGroup sideBySide compact>
                <FormGroupLabel>{t("Unit")}</FormGroupLabel>
                <FormGroupInput>
                    {(props) => (
                        <AutoComplete
                            size="small"
                            value={UNIT_VALUES[unit]}
                            onChange={(unit) => {
                                setOperation({ ...operation, unit });
                            }}
                            options={Object.entries(UNIT_VALUES).map(([value, label]) => ({
                                label,
                                value,
                            }))}
                        />
                    )}
                </FormGroupInput>
            </FormGroup>
        </>
    );
};

interface IDateSelectorProps {
    date: Date;
    onSelect(date: Date);
}

/**
 * Renders the date picker
 */
const DateSelector = (props: IDateSelectorProps) => {
    const { date, onSelect } = props;
    const classes = rangePickerClasses();
    const formClasses = rangeFormClasses();
    return (
        <DayPicker
            className={cx(classes.picker, formClasses.datePicker)}
            pagedNavigation
            fixedWeeks
            selectedDays={[date]}
            month={date}
            onDayClick={(date) => onSelect(date)}
            disabledDays={{ after: new Date() }}
            navbarElement={DatePickerNav}
            captionElement={() => <></>}
        />
    );
};

/**
 * Renders the range form
 */
export function RangeForm(props: IDateModifierPickerProps & { defaultOperation: DateOperation }) {
    const { dateModifier, setDateModifier, defaultOperation } = props;
    const classes = rangeFormClasses();
    const { date, operations } = dateModifier;

    // Determine the current date type.
    let dateType: keyof typeof DATE_VALUES = "today";
    if (date) {
        dateType = "fixed";
    } else if (operations && operations[0]) {
        const firstOperation = operations[0];
        if (firstOperation.type === "startOf" && firstOperation.unit === "week") {
            dateType = "startOfWeek";
        } else if (firstOperation.type === "startOf" && firstOperation.unit === "month") {
            dateType = "startOfMonth";
        } else if (firstOperation.type === "endOf" && firstOperation.unit === "week") {
            dateType = "endOfWeek";
        } else if (firstOperation.type === "endOf" && firstOperation.unit === "month") {
            dateType = "endOfMonth";
        }
    }

    const lastOperation = operations && operations.slice(-1)[0];
    const addSubtractOperation = lastOperation && ["add", "subtract"].includes(lastOperation.type) && lastOperation;
    const isRelative = Boolean(!date || addSubtractOperation);

    const handleDateSelection = (value: string) => {
        switch (value) {
            case "fixed":
                setDateModifier({ date: applyDateModifier(dateModifier) });
                break;
            case "today":
                setDateModifier({ operations: [defaultOperation] });
                break;
            case "startOfWeek":
                setDateModifier({ operations: [{ type: "startOf", unit: "week" }, defaultOperation] });
                break;
            case "startOfMonth":
                setDateModifier({
                    date: undefined,
                    operations: [{ type: "startOf", unit: "month" }, defaultOperation],
                });
                break;
            case "endOfWeek":
                setDateModifier({ operations: [{ type: "endOf", unit: "week" }, defaultOperation] });
                break;
            case "endOfMonth":
                setDateModifier({ operations: [{ type: "endOf", unit: "month" }, defaultOperation] });
                break;
        }
    };

    return (
        <>
            <FormGroup sideBySide compact>
                <FormGroupLabel>{t("Date")}</FormGroupLabel>
                <FormGroupInput>
                    {(props) => (
                        <AutoComplete
                            size="small"
                            value={DATE_VALUES[dateType]}
                            onChange={handleDateSelection}
                            options={Object.entries(DATE_VALUES).map(([value, label]) => ({
                                label,
                                value,
                            }))}
                        />
                    )}
                </FormGroupInput>
            </FormGroup>
            {isRelative ? (
                <OperationPicker
                    operation={addSubtractOperation || defaultOperation}
                    setOperation={(operation) => setDateModifier({ ...dateModifier, operations: [operation] })}
                />
            ) : (
                <DateSelector
                    date={date || new Date()}
                    onSelect={(date) => setDateModifier({ ...dateModifier, date })}
                />
            )}
        </>
    );
}
