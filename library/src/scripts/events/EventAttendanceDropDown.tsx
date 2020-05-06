import { t } from "@library/utility/appUtils";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import * as React from "react";
import { eventsClasses } from "@library/events/eventStyles";

export enum EventAttendance {
    GOING = "going",
    MAYBE = "maybe",
    NOT_GOING = "not going",
}

/**
 * Component for displaying/selecting attendance to an event
 */
export default function EventAttendanceDropDown(props: { attendance: EventAttendance; options: ISelectBoxItem[] }) {
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
                value={activeOption}
                renderLeft={true}
                offsetPadding={true}
            />
        </>
    );
}
