/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useMemo, useState } from "react";
import DayPicker, { DateUtils, RangeModifier } from "react-day-picker";
import moment from "moment";
import "react-day-picker/lib/style.css";
import DatePickerNav from "@library/forms/rangePicker/DatePickerNav";
import { rangePickerClasses } from "./RangePicker.styles";
import { IDateModifierRangePickerProps } from "@library/forms/rangePicker/types";
import { applyDateModifier, dateModifier } from "@library/forms/rangePicker/utils";

function addDateToRange(rangeModifier: RangeModifier, date: Date) {
    if (rangeModifier) {
        return DateUtils.addDayToRange(date, rangeModifier);
    }
    return { from: date, to: undefined };
}

export function RangePicker(props: IDateModifierRangePickerProps) {
    const { range, setRange } = props;
    const { from, to } = range;
    const fromDate = useMemo(() => applyDateModifier(from), [from]);
    const toDate = useMemo(() => applyDateModifier(to), [to]);
    const rangeModifier = useMemo(
        () => ({
            from: fromDate,
            to: toDate,
        }),
        [fromDate, toDate],
    );
    const modifiers = useMemo(
        () => ({
            start: rangeModifier.from,
            end: rangeModifier.to,
        }),
        [rangeModifier],
    );
    const classes = rangePickerClasses();
    const fromMonth = useMemo(() => moment(fromDate).add(1, "month").toDate(), [fromDate]);
    const toMonth = useMemo(() => moment(toDate).subtract(1, "month").toDate(), [toDate]);

    const handleClick = (date: Date) => {
        if (!setRange) return;
        const { from, to } = DateUtils.addDayToRange(date, rangeModifier);
        setRange({
            from: dateModifier(from!).build(),
            to: dateModifier(to!).build(),
        });
    };

    return (
        <section className={classes.container}>
            <DayPicker
                className={classes.picker}
                // Always render this and the previous month
                month={new Date(new Date().setMonth(new Date().getMonth() - 1))}
                pagedNavigation
                fixedWeeks
                selectedDays={rangeModifier}
                modifiers={modifiers}
                onDayClick={handleClick}
                disabledDays={{ after: new Date() }}
                navbarElement={DatePickerNav}
                toMonth={toMonth}
                captionElement={() => <></>}
            />
            <DayPicker
                className={classes.picker}
                pagedNavigation
                fixedWeeks
                selectedDays={rangeModifier}
                modifiers={modifiers}
                onDayClick={handleClick}
                disabledDays={{ after: new Date() }}
                navbarElement={DatePickerNav}
                toMonth={new Date()}
                fromMonth={fromMonth}
                captionElement={() => <></>}
            />
        </section>
    );
}
