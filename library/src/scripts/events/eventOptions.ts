/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { t } from "@vanilla/i18n/src";

export enum EventAttendance {
    RSVP = "rsvp", // only for default value in EventAttendanceDropDown
    GOING = "yes",
    MAYBE = "maybe",
    NOT_GOING = "not",
}

export const eventAttendanceOptions = [
    { name: t("Going"), value: EventAttendance.GOING },
    { name: t("Maybe"), value: EventAttendance.MAYBE },
    { name: t("Not going"), value: EventAttendance.NOT_GOING },
] as ISelectBoxItem[];
