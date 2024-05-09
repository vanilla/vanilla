import { t } from "@vanilla/i18n";
import { sprintf } from "sprintf-js";

/**
 * Return a date in relation to a time like Momement.from/fromNow. By default we use "now".
 *
 * @param datePosition
 * @param dateFrom
 * @param withPrefixOrSuffix
 * @returns
 */
export function humanizedRelativeTime(dateInput: Date, dateFrom: Date = new Date(), withPrefixOrSuffix = true) {
    const isPast = dateFrom.getTime() > dateInput.getTime();
    const delta = Math.abs(
        Math.round(
            (isPast ? dateInput.getTime() - dateFrom.getTime() : dateFrom.getTime() - dateInput.getTime()) / 1000,
        ),
    );

    const minToSec = 60;
    const hourToSec = 3600;
    const dayToSec = 86400;
    const monthToSec = 2629744;
    const yearToSec = 31556926;

    let deltaString = "";
    let deltaStringWithPrefixOrSuffix = "";

    if (delta > 548 * dayToSec) {
        const years = Math.round(delta / yearToSec);
        deltaString = sprintf(t("%s years"), years);
        deltaStringWithPrefixOrSuffix = isPast
            ? sprintf(t("%s ago"), t(sprintf(t("%s years"), years)))
            : sprintf(t("in %s"), t(sprintf(t("%s years"), years)));
    }
    if (delta >= 320 * dayToSec && delta < 548 * dayToSec) {
        deltaString = t("a year");
        deltaStringWithPrefixOrSuffix = isPast ? sprintf(t("%s ago"), t("a year")) : sprintf(t("in %s"), t("a year"));
    }
    if (delta >= 45 * dayToSec && delta < 320 * dayToSec) {
        const months = Math.round(delta / monthToSec);
        deltaString = sprintf(t("%s months"), months);
        deltaStringWithPrefixOrSuffix = isPast
            ? sprintf(t("%s ago"), t(sprintf(t("%s months"), months)))
            : sprintf(t("in %s"), t(sprintf(t("%s months"), months)));
    }
    if (delta >= 26 * dayToSec && delta < 45 * dayToSec) {
        deltaString = t("a month");
        deltaStringWithPrefixOrSuffix = isPast ? sprintf(t("%s ago"), t("a month")) : sprintf(t("in %s"), t("a month"));
    }
    if (delta >= 36 * 60 * 60 && delta < 26 * dayToSec) {
        const days = Math.round(delta / dayToSec);
        deltaString = sprintf(t("%s days"), days);
        deltaStringWithPrefixOrSuffix = isPast
            ? sprintf(t("%s ago"), t(sprintf(t("%s days"), days)))
            : sprintf(t("in %s"), t(sprintf(t("%s days"), days)));
    }
    if (delta >= 22 * hourToSec && delta < 36 * hourToSec) {
        deltaString = t("a day");
        deltaStringWithPrefixOrSuffix = isPast ? sprintf(t("%s ago"), t("a day")) : sprintf(t("in %s"), t("a day"));
    }
    if (delta >= 90 * minToSec && delta < 22 * hourToSec) {
        const hours = Math.round(delta / hourToSec);
        deltaString = sprintf(t("%s hours"), hours);
        deltaStringWithPrefixOrSuffix = isPast
            ? sprintf(t("%s ago"), t(sprintf(t("%s hours"), hours)))
            : sprintf(t("in %s"), t(sprintf(t("%s hours"), hours)));
    }
    if (delta >= 45 * minToSec && delta < 90 * minToSec) {
        deltaString = t("an hour");
        deltaStringWithPrefixOrSuffix = isPast ? sprintf(t("%s ago"), t("an hour")) : sprintf(t("in %s"), t("an hour"));
    }
    if (delta >= 90 && delta < 45 * minToSec) {
        const minutes = Math.round(delta / minToSec);
        deltaString = sprintf(t("%s minutes"), minutes);
        deltaStringWithPrefixOrSuffix = isPast
            ? sprintf(t("%s ago"), t(sprintf(t("%s minutes"), minutes)))
            : sprintf(t("in %s"), t(sprintf(t("%s minutes"), minutes)));
    }
    if (delta >= 45 && delta < 90) {
        deltaString = t("a minute");
        deltaStringWithPrefixOrSuffix = isPast
            ? sprintf(t("%s ago"), t("a minute"))
            : sprintf(t("in %s"), t("a minute"));
    }
    if (delta < 45) {
        const seconds = Math.floor(delta);
        deltaString = sprintf(t("%s seconds"), seconds);
        deltaStringWithPrefixOrSuffix = isPast
            ? sprintf(t("%s ago"), t(sprintf(t("%s seconds"), seconds)))
            : sprintf(t("in %s"), t(sprintf(t("%s seconds"), seconds)));
    }
    if (delta < 44) {
        deltaString = t("a few seconds");
        deltaStringWithPrefixOrSuffix = isPast
            ? sprintf(t("%s ago"), t("a few seconds"))
            : sprintf(t("in %s"), t("a few seconds"));
    }

    return withPrefixOrSuffix ? deltaStringWithPrefixOrSuffix : deltaString;
}

export enum DateElement {
    YEAR = "year",
    MONTH = "month",
    DAY = "day",
}

/**
 * Check if a date is the same as another date.
 *
 * @param date1
 * @param date2
 * @param precision
 * @returns
 */
export function isSameDate(date1: Date, date2: Date, precision: DateElement) {
    const yearIsSame = date1.getFullYear() === date2.getFullYear();
    const monthIsSame = yearIsSame && date1.getMonth() === date2.getMonth();
    const dayIsSame = monthIsSame && date1.getDate() === date2.getDate();
    switch (precision) {
        case DateElement.YEAR:
            return yearIsSame;
        case DateElement.MONTH:
            return monthIsSame;
        case DateElement.DAY:
            return dayIsSame;
    }
}

/**
 * Check if a date is the same as or after another date.
 *
 * @param date1
 * @param date2
 * @param precision
 * @returns
 */
export function isSameOrAfterDate(date1: Date, date2: Date, precision?: DateElement) {
    const yearIsSameOrAfter = date1.getFullYear() >= date2.getFullYear();
    const monthIsSameOrAfter = yearIsSameOrAfter && date1.getMonth() >= date2.getMonth();
    const dayIsSameOrAfter = monthIsSameOrAfter && date1.getDate() >= date2.getDate();
    const msIsSameOrAfter = date1.getTime() >= date2.getTime();
    switch (precision) {
        case DateElement.YEAR:
            return yearIsSameOrAfter;
        case DateElement.MONTH:
            return monthIsSameOrAfter;
        case DateElement.DAY:
            return dayIsSameOrAfter;
        default:
            return msIsSameOrAfter;
    }
}

/**
 * Get a new date by subtracting days from a date.
 *
 * @param date
 * @param days
 * @returns
 */
export function getDateBysubStractDays(date: Date, days: number): Date {
    return new Date(date.getTime() - days * 24 * 60 * 60 * 1000);
}
