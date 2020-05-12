/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import DateTime, { DateFormats, IDateTime } from "@library/content/DateTime";

export interface IFromToDate extends Omit<IDateTime, "type"> {}

export interface IProps {
    dateStart: IFromToDate;
    dateEnd?: IFromToDate;
}

/**
 * Component for displaying a block of time.
 */
export function FromToDateTime(props: IProps) {
    // Note that we plan to have more advanced checks here to not repeat duplicate date/time information in the end time in a future iteration.
    const startDate = <DateTime {...props.dateStart} type={DateFormats.EXTENDED} />;
    const endDate = <DateTime {...props.dateEnd} type={DateFormats.EXTENDED} />;
    return (
        <>
            {startDate}
            {endDate ? ` - ${endDate}` : ""}
        </>
    );
}
