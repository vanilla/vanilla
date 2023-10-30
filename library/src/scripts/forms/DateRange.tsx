/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import React, { ComponentProps } from "react";
import { dateRangeClasses } from "@library/forms/dateRangeStyles";
import { t } from "@library/utility/appUtils";
import DatePicker from "@library/forms/DatePicker";
import moment from "moment";
import { inputClasses } from "./inputStyles";

interface IProps {
    start: string | undefined;
    end: string | undefined;
    onStartChange: (value: string) => void;
    onEndChange: (value: string) => void;
    datePickerDropdownClassName?: ComponentProps<typeof DatePicker>["datePickerDropdownClassName"];
}

/**
 * Implements the DateRange component
 */
export default function DateRange(props: IProps) {
    const endDate = props.end ? moment(rectifyDate(props.end)) : null;
    const startDate = props.start ? moment(rectifyDate(props.start)) : null;

    const fromLabel = t("From");
    const toLabel = t("To");
    const rangeClasses = dateRangeClasses();

    return (
        <>
            <label className={rangeClasses.boundary}>
                <span className={rangeClasses.label}>{fromLabel}</span>
                <DatePicker
                    alignment="right"
                    inputClassName={rangeClasses.input}
                    onChange={(value) => {
                        const momentValue = moment(value);
                        const newValue = momentValue.isValid()
                            ? moment(startDate ?? undefined)
                                  .set({
                                      year: momentValue.get("year"),
                                      month: momentValue.get("month"),
                                      date: momentValue.get("date"),
                                  })
                                  .format("YYYY-MM-DD")
                            : "";

                        props.onStartChange(newValue);
                    }}
                    value={startDate ? startDate.format("YYYY-MM-DD") : ""}
                    max={endDate ? moment(endDate).format("YYYY-MM-DD") : ""}
                    datePickerDropdownClassName={props.datePickerDropdownClassName}
                />
            </label>

            <label className={rangeClasses.boundary}>
                <span className={rangeClasses.label}>{toLabel}</span>
                <DatePicker
                    alignment="right"
                    inputClassName={rangeClasses.input}
                    onChange={(value) => {
                        const momentValue = moment(value);
                        const newValue = momentValue.isValid()
                            ? moment(endDate ?? undefined)
                                  .set({
                                      year: momentValue.get("year"),
                                      month: momentValue.get("month"),
                                      date: momentValue.get("date"),
                                  })
                                  .format("YYYY-MM-DD")
                            : "";

                        props.onEndChange(newValue);
                    }}
                    value={endDate ? endDate?.format("YYYY-MM-DD") : ""}
                    min={startDate ? moment(startDate).format("YYYY-MM-DD") : ""}
                    datePickerDropdownClassName={props.datePickerDropdownClassName}
                />
            </label>
        </>
    );
}

/**
 * Return a valid date which can be passed to moment or a date object
 *
 * Strips >=, <= or = from date strings
 */
function rectifyDate(date: string): string {
    return date.replace(/(>|<)?=/, "");
}
