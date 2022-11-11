/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useMemo } from "react";
import { RangeForm } from "./RangeForm";
import { advancedRangePickerClasses } from "@library/forms/rangePicker/AdvancedRangePicker.styles";
import moment from "moment";
import { IDateModifierRangePickerProps } from "@library/forms/rangePicker/types";
import { applyDateModifier } from "@library/forms/rangePicker/utils";

/**
 * This components renders a start and end date form picker and
 * will output valid ranges starting at 00h00 and ending at 23h59
 */
export function AdvancedRangePicker(props: IDateModifierRangePickerProps) {
    const { range, setRange } = props;
    const { from, to } = range;
    const fromDate = useMemo(() => applyDateModifier(from), [from]);
    const toDate = useMemo(() => applyDateModifier(to), [to]);

    const classes = advancedRangePickerClasses();

    /**
     * Validates that the end of the range is after the start and that the end of the range is not in the future
     */
    const isRangeValid = useMemo(() => {
        return moment().isAfter(fromDate) && moment(moment().add(1, "days")).isAfter(toDate);
    }, [fromDate, toDate]);

    return (
        <>
            <section className={classes.layout}>
                <div className={classes.form}>
                    <h4>Start Date</h4>
                    <RangeForm
                        dateModifier={from}
                        defaultOperation={{ type: "subtract", amount: 15, unit: "days" }}
                        setDateModifier={(dateModifier) => setRange({ ...range, from: dateModifier })}
                    />
                </div>
                <div className={classes.form}>
                    <h4>End Date</h4>
                    <RangeForm
                        dateModifier={to}
                        defaultOperation={{ type: "subtract", amount: 0, unit: "days" }}
                        setDateModifier={(dateModifier) => setRange({ ...range, to: dateModifier })}
                    />
                </div>
            </section>
            {!isRangeValid && (
                <span className={classes.invalid}>
                    The date range {fromDate?.toLocaleDateString()} - {toDate?.toLocaleDateString()} is invalid
                </span>
            )}
        </>
    );
}
