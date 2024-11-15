/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useMemo } from "react";
import DayPicker, { DateUtils, Modifiers, RangeModifier } from "react-day-picker";
import moment from "moment";
import "react-day-picker/lib/style.css";
import DatePickerNav from "@library/forms/rangePicker/DatePickerNav";
import { rangePickerClasses } from "./RangePicker.styles";
import { IDateModifierRangePickerProps } from "@library/forms/rangePicker/types";
import { applyDateModifier, dateModifier } from "@library/forms/rangePicker/utils";

export function RangePicker(props: IDateModifierRangePickerProps) {
    const { range, setRange } = props;
    const { from, to } = range;

    const fromDate = useMemo(() => applyDateModifier(from), [from]);
    const toDate = useMemo(() => applyDateModifier(to), [to]);

    const rangeModifier: RangeModifier = useMemo(
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

    const handleClick = (date: Date) => {
        if (!setRange) return;

        const closest = [fromDate, toDate].sort((a, b) => {
            const deltaA = Math.abs(date.getTime() - a.getTime());
            const deltaB = Math.abs(date.getTime() - b.getTime());
            return deltaA - deltaB;
        })[0];

        const toModify = closest === fromDate ? "from" : "to";
        let sameDay = false;
        if (rangeModifier.from && rangeModifier.to) {
            sameDay = DateUtils.isSameDay(date, rangeModifier.to) || DateUtils.isSameDay(date, rangeModifier.from);
        }

        if (toModify === "from") {
            rangeModifier.from = date;
        }
        if (toModify === "to") {
            rangeModifier.to = date;
        }
        if (sameDay) {
            rangeModifier.from = moment(date).startOf("day").toDate();
            rangeModifier.to = moment(date).endOf("day").toDate();
        }

        setRange({
            from: dateModifier(rangeModifier?.from ?? undefined).build(),
            to: dateModifier(rangeModifier?.to ?? undefined).build(),
        });
    };

    const isRangeValid = useMemo(() => {
        return moment(toDate).isAfter(fromDate);
    }, [fromDate, toDate]);

    return (
        <>
            <section className={classes.container}>
                <DayPicker
                    className={classes.picker}
                    // Always render this and the previous month
                    month={fromDate}
                    pagedNavigation
                    fixedWeeks
                    selectedDays={rangeModifier}
                    modifiers={modifiers as Partial<Modifiers>}
                    onDayClick={handleClick}
                    disabledDays={{ after: new Date() }}
                    navbarElement={DatePickerNav}
                    toMonth={toDate}
                    captionElement={() => <></>}
                />
                <DayPicker
                    className={classes.picker}
                    month={toDate}
                    pagedNavigation
                    fixedWeeks
                    selectedDays={rangeModifier}
                    modifiers={modifiers as Partial<Modifiers>}
                    onDayClick={handleClick}
                    disabledDays={{ after: new Date() }}
                    navbarElement={DatePickerNav}
                    toMonth={new Date()}
                    fromMonth={fromDate}
                    captionElement={() => <></>}
                />
            </section>
            {!isRangeValid && (
                <span className={classes.invalid}>
                    The date range {fromDate?.toLocaleDateString()} - {toDate?.toLocaleDateString()} is invalid
                </span>
            )}
        </>
    );
}
