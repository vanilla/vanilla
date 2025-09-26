/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "@library/utility/appUtils";
import { useEffect, useState } from "react";
import { getJSLocaleKey } from "@vanilla/i18n";
import { dateTimeClasses } from "@library/content/dateTimeStyles";
import { DateElement, humanizedRelativeTime, isSameDate } from "@library/content/DateTimeHelpers";

export const DateFormats = {
    DEFAULT: "default",
    EXTENDED: "extended",
    COMPACT: "compact",
    TIME: "time",
} as const;

export type DateFormats = (typeof DateFormats)[keyof typeof DateFormats];

export type IDateTimeProps = {
    /** Pass an explicit time zone to format in. */
    timezone?: string;
    /** An additional classname to apply to the root of the component */
    className?: string;
    /** Display a fixed or relative visible time. */
    mode?: "relative" | "fixed";
    type?: DateFormats;
    isSameYear?: boolean;
} & (
    | {
          /**
           * @deprecated
           * Use `date` instead.
           */
          timestamp: string | undefined;
          date?: never;
      }
    | {
          /**
           * @deprecated
           * Use `date` instead.
           */
          timestamp?: never;
          /** A javascript date object, or UTC/ISO Date string */
          date: string | Date;
      }
);

/**
 * Component for displaying an accessible nicely formatted time string.
 */
const DateTime = (props: IDateTimeProps) => {
    const { timezone, className, mode = "fixed", type = DateFormats.DEFAULT, isSameYear } = props;
    const [forceUpdate, setForceUpdate] = useState(0);

    let dateObject: Date;
    let dateString: string;
    try {
        const { date, timestamp } = props;
        dateObject = timestamp && timestamp.length > 0 ? new Date(timestamp) : date ? new Date(date) : new Date();
        dateString = dateObject.toISOString();
    } catch (e) {
        // In case we get an invalid date.
        dateObject = new Date("1970-01-01T00:00:00Z");
        dateString = dateObject.toISOString();
    }

    useEffect(() => {
        if (mode === "relative") {
            const interval = setInterval(() => {
                setForceUpdate(new Date().getTime());
            }, 30000);
            return () => clearInterval(interval);
        }
    }, []);

    const titleTime = () => {
        const date = dateObject;
        return date.toLocaleString(getJSLocaleKey(), {
            year: "numeric",
            month: "long",
            day: "numeric",
            weekday: "long",
            hour: "numeric",
            minute: "numeric",
            timeZone: timezone,
        });
    };

    const options = (): Intl.DateTimeFormatOptions => {
        switch (type) {
            case DateFormats.EXTENDED:
                return {
                    ...(!isSameYear && { year: "numeric" }),
                    month: "short",
                    day: "numeric",
                    hour: "numeric",
                    minute: "numeric",
                    timeZone: timezone,
                };
            case DateFormats.TIME:
                return {
                    hour: "numeric",
                    minute: "numeric",
                    timeZone: timezone,
                };
            default:
                return {
                    ...(!isSameYear && { year: "numeric" }),
                    month: "short",
                    day: "numeric",
                    timeZone: timezone,
                };
        }
    };

    const humanTime = () => {
        const inputDateObject = dateObject;
        const nowDate = new Date();

        const localeKey = getJSLocaleKey();

        if (mode === "relative") {
            const seconds = (nowDate.getTime() - inputDateObject.getTime()) / 1000;
            if (seconds >= 0 && seconds <= 5) {
                return t("just now");
            }
            return humanizedRelativeTime(inputDateObject, nowDate);
        } else {
            if (type !== DateFormats.COMPACT) {
                if (isSameDate(inputDateObject, nowDate, DateElement.DAY)) {
                    return inputDateObject
                        .toLocaleString(localeKey, {
                            hour: "numeric",
                            minute: "numeric",
                            timeZone: timezone,
                        })
                        .toLowerCase();
                }
                return inputDateObject.toLocaleString(localeKey, options());
            } else {
                const classes = dateTimeClasses();
                return (
                    <span className={classes.compactRoot}>
                        <span className={classes.compactMonth} key={"month"}>
                            {inputDateObject.toLocaleString(localeKey, {
                                month: "short",
                                timeZone: timezone,
                            })}
                        </span>
                        <span className={classes.compactDay} key={"day"}>
                            {inputDateObject.toLocaleString(localeKey, {
                                day: "numeric",
                                timeZone: timezone,
                            })}
                        </span>
                    </span>
                );
            }
        }
    };

    return (
        <time key={forceUpdate} className={className} dateTime={dateString} title={titleTime()}>
            {humanTime()}
        </time>
    );
};

export default DateTime;
