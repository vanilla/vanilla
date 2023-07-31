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
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { useUniqueID } from "@library/utility/idUtils";
import classNames from "classnames";

interface IProps {
    start: string | undefined;
    end: string | undefined;
    onStartChange: (value: string) => void;
    onEndChange: (value: string) => void;
    label?: string;
    className?: string;
    datePickerDropdownClassName?: ComponentProps<typeof DatePicker>["datePickerDropdownClassName"];
}

/**
 * Implements the DateRange component
 */
export default function DateRange(props: IProps) {
    const endDate = props.end ? moment(rectifyDate(props.end)).toDate() : null;
    const startDate = props.start ? moment(rectifyDate(props.start)).toDate() : null;
    const fromLabel = t("From");
    const toLabel = t("To");
    const rangeClasses = dateRangeClasses();
    const classesInputBlock = inputBlockClasses();
    const labelID = useUniqueID("dateRange");

    return (
        <div
            role="group"
            className={classNames(classesInputBlock.root, rangeClasses.root, props.className)}
            aria-labelledby={props.label ? labelID : undefined}
        >
            {!!props.label && (
                <div id={labelID} className={classesInputBlock.sectionTitle}>
                    {props.label}
                </div>
            )}
            <label className={rangeClasses.boundary}>
                <span className={rangeClasses.label}>{fromLabel}</span>
                <DatePicker
                    alignment="right"
                    contentClassName={rangeClasses.input}
                    onChange={props.onStartChange}
                    value={props.start && rectifyDate(props.start)}
                    disabledDays={[
                        {
                            after: endDate,
                        },
                    ]}
                    datePickerDropdownClassName={props.datePickerDropdownClassName}
                />
            </label>
            <label className={rangeClasses.boundary}>
                <span className={rangeClasses.label}>{toLabel}</span>
                <DatePicker
                    alignment="right"
                    contentClassName={rangeClasses.input}
                    onChange={props.onEndChange}
                    value={props.end && rectifyDate(props.end)}
                    disabledDays={[
                        {
                            before: startDate,
                        },
                    ]}
                    datePickerDropdownClassName={props.datePickerDropdownClassName}
                />
            </label>
        </div>
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
