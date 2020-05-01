import classNames from "classnames";
import Heading from "@library/layout/Heading";
import { t } from "@library/utility/appUtils";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import * as React from "react";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";

export enum EventAttendance {
    GOING = "going",
    MAYBE = "maybe",
    NOT_GOING = "not going",
}

// Don't forget to add translation strings

export default function EventAttendanceDropDown(props: { attendance: EventAttendance }) {
    const labelDescription = uniqueIDFromPrefix("eventAttendanceLabel");
    const options = [
        { label: t("Going"), value: EventAttendance.GOING },
        { label: t("Maybe"), value: EventAttendance.MAYBE },
        { label: t("Not going"), value: EventAttendance.NOT_GOING },
    ];

    let activeOption = options.find(option => option.value === props.attendance);

    return (
        <>
            <ScreenReaderContent id={labelDescription}>{t("Will you be attending?")}</ScreenReaderContent>
            <SelectBox
                widthOfParent={true}
                options={options}
                describedBy={labelDescription}
                value={activeOption}
                renderLeft={false}
            />
        </>
    );
}
