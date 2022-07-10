/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "@library/utility/appUtils";
import React, { Component } from "react";
import { getJSLocaleKey } from "@vanilla/i18n";
import { dateTimeClasses } from "@library/content/dateTimeStyles";
import { DateElement, humanizedTimeFrom, isSameDate } from "@library/content/DateTimeHelpers";

export enum DateFormats {
    DEFAULT = "default",
    EXTENDED = "extended",
    COMPACT = "compact",
}

interface IDateTimeProps {
    /** The timestamp to format and display */
    timestamp: string;
    /** Pass an explicit time zone to format in. */
    timezone?: string;
    /** An additional classname to apply to the root of the component */
    className?: string;
    /** Display a fixed or relative visible time. */
    mode?: "relative" | "fixed";
    type?: DateFormats;
}

/**
 * Component for displaying an accessible nicely formatted time string.
 */
export default class DateTime extends Component<IDateTimeProps> {
    public static defaultProps: Partial<IDateTimeProps> = {
        mode: "fixed",
        type: DateFormats.DEFAULT,
    };
    private interval;

    public render() {
        return (
            <time className={this.props.className} dateTime={this.props.timestamp} title={this.titleTime}>
                {this.humanTime}
            </time>
        );
    }

    public componentDidMount() {
        if (this.props.mode === "relative") {
            this.interval = setInterval(() => {
                this.forceUpdate();
            }, 30000);
        }
    }

    public componentWillUnmount() {
        if (this.interval) {
            clearInterval(this.interval);
        }
    }

    /**
     * Get the title of the time tag (long extended date)
     */
    private get titleTime(): string {
        const date = new Date(this.props.timestamp);
        return date.toLocaleString(getJSLocaleKey(), {
            year: "numeric",
            month: "long",
            day: "numeric",
            weekday: "long",
            hour: "numeric",
            minute: "numeric",
            timeZone: this.props.timezone,
        });
    }

    private get options(): Intl.DateTimeFormatOptions {
        switch (this.props.type) {
            case DateFormats.EXTENDED:
                return {
                    year: "numeric",
                    month: "short",
                    day: "numeric",
                    hour: "numeric",
                    minute: "numeric",
                    timeZone: this.props.timezone,
                };
            default:
                return {
                    year: "numeric",
                    month: "short",
                    day: "numeric",
                    timeZone: this.props.timezone,
                };
        }
    }

    /**
     * Get a shorter human readable time for the time tag.
     */
    private get humanTime(): React.ReactNode {
        const inputDateObject = new Date(this.props.timestamp);
        const nowDate = new Date();

        const localeKey = getJSLocaleKey();

        if (this.props.mode === "relative") {
            const seconds = (nowDate.getTime() - inputDateObject.getTime()) / 1000;
            if (seconds >= 0 && seconds <= 5) {
                return t("just now");
            }
            return humanizedTimeFrom(inputDateObject, nowDate);
        } else {
            if (this.props.type !== DateFormats.COMPACT) {
                // If it's the same day, return the time.
                if (isSameDate(inputDateObject, nowDate, DateElement.DAY)) {
                    return inputDateObject
                        .toLocaleString(localeKey, {
                            hour: "numeric",
                            minute: "numeric",
                            timeZone: this.props.timezone,
                        })
                        .toLowerCase();
                }
                // Otherwise return the date.
                return inputDateObject.toLocaleString(localeKey, this.options);
            } else {
                const classes = dateTimeClasses();
                return (
                    <span className={classes.compactRoot}>
                        <span className={classes.compactMonth} key={"month"}>
                            {inputDateObject.toLocaleString(localeKey, {
                                month: "short",
                                timeZone: this.props.timezone,
                            })}
                        </span>
                        <span className={classes.compactDay} key={"day"}>
                            {inputDateObject.toLocaleString(localeKey, {
                                day: "numeric",
                                timeZone: this.props.timezone,
                            })}
                        </span>
                    </span>
                );
            }
        }
    }
}
