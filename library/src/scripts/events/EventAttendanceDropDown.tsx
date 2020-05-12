/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "@library/utility/appUtils";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import * as React from "react";
import { eventsClasses } from "@library/events/eventStyles";
import { EventAttendance } from "@library/events/eventOptions";

export interface IEventAttendance {
    attendance: EventAttendance;
    options: ISelectBoxItem[];
}

/**
 * Component for displaying/selecting attendance to an event
 */
export default function EventAttendanceDropDown(props: IEventAttendance) {
    if (props.options.length === 0) {
        return null;
    }

    const activeOption = props.options.find(option => option.value === props.attendance);

    return (
        <>
            <SelectBox
                className={eventsClasses().dropDown}
                widthOfParent={false}
                options={props.options}
                label={t("Will you be attending?")}
                value={
                    activeOption ?? {
                        name: t("RSVP"),
                        value: EventAttendance.RSVP,
                    }
                }
                renderLeft={true}
                offsetPadding={true}
            />
        </>
    );
}
