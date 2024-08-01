/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { DateFormats } from "@library/content/DateTime";
import DateTime from "@library/content/DateTime";
import { isSameDate, DateElement } from "@library/content/DateTimeHelpers";

export interface IProps {
    dateStarts: string;
    dateEnds?: string;
}

/**
 * Component for displaying a block of time.
 */
export function FromToDateTime(props: IProps) {
    const { dateStarts, dateEnds } = props;
    const startSameYear = isSameDate(new Date(dateStarts), new Date(), DateElement.YEAR);
    const endSameYear = dateEnds ? isSameDate(new Date(dateEnds), new Date(), DateElement.YEAR) : false;
    const isSameDay = dateEnds ? isSameDate(new Date(dateStarts), new Date(dateEnds), DateElement.DAY) : false;
    const secondFormat = isSameDay ? DateFormats.TIME : DateFormats.EXTENDED;

    const startDate = <DateTime timestamp={props.dateStarts} type={DateFormats.EXTENDED} isSameYear={startSameYear} />;
    const endDate = props.dateEnds ? (
        <DateTime timestamp={props.dateEnds} type={secondFormat} isSameYear={endSameYear} />
    ) : undefined;

    return (
        <>
            {startDate}
            {props.dateEnds && (
                <>
                    {" "}
                    {endDate && " - "}
                    {endDate}
                </>
            )}
        </>
    );
}
