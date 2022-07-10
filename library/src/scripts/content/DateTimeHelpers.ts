/**
 * Return a date in relation to a time like Momement.from/fromNow. By default we use "now".
 *
 * @param datePosition
 * @param dateFrom
 * @returns
 */
export function humanizedTimeFrom(dateInput: Date, dateFrom: Date = new Date()) {
    const delta = Math.round((dateFrom.getTime() - dateInput.getTime()) / 1000);
    const minToSec = 60;
    const hourToSec = 3600;
    const dayToSec = 86400;
    const monthToSec = 2629744;
    const yearToSec = 31556926;
    if (delta < 44) {
        return "a few seconds ago";
    }
    if (delta < 45) {
        return Math.floor(delta) + " seconds ago";
    }
    if (delta >= 45 && delta < 90) {
        return "a minute ago";
    }
    if (delta >= 90 && delta < 45 * minToSec) {
        return Math.round(delta / minToSec) + " minutes ago";
    }
    if (delta >= 45 * minToSec && delta < 90 * minToSec) {
        return "an hour ago";
    }
    if (delta >= 90 * minToSec && delta < 22 * hourToSec) {
        return Math.round(delta / hourToSec) + " hours ago";
    }
    if (delta >= 22 * hourToSec && delta < 36 * hourToSec) {
        return "a day ago";
    }
    if (delta >= 36 * 60 * 60 && delta < 26 * dayToSec) {
        return Math.round(delta / dayToSec) + " days ago";
    }
    if (delta >= 26 * dayToSec && delta < 45 * dayToSec) {
        return "a month ago";
    }
    if (delta >= 45 * dayToSec && delta < 320 * dayToSec) {
        return Math.round(delta / monthToSec) + " months ago";
    }
    if (delta >= 320 * dayToSec && delta < 548 * dayToSec) {
        return "a year ago";
    }
    return Math.round(delta / yearToSec) + " years ago";
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
 * Get a new date by substractin days from a date.
 *
 * @param date
 * @param days
 * @returns
 */
export function getDateBysubStractDays(date: Date, days: number): Date {
    return new Date(date.getTime() - days * 24 * 60 * 60 * 1000);
}
