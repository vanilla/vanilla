/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { buttonClasses } from "@library/forms/Button.styles";
import { RadioGroup } from "@library/forms/radioAsButtons/RadioGroup";
import RadioInputAsButton from "@library/forms/radioAsButtons/RadioInputAsButton";
import { searchInFilterClasses } from "@library/search/searchInFilter.styles";
import { t } from "@vanilla/i18n";
import { labelize } from "@vanilla/utils";
import { IDateModifierRange, TimeInterval } from "@library/forms/rangePicker/types";
import { timeFrameFromDateModifierRange } from "@library/forms/rangePicker/utils";
import moment from "moment";
import React, { useMemo } from "react";
import Message from "@library/messages/Message";
import { css } from "@emotion/css";
import { Icon } from "@vanilla/icons";

const intervals: TimeInterval[] = [
    TimeInterval.HOURLY,
    TimeInterval.DAILY,
    TimeInterval.WEEKLY,
    TimeInterval.MONTHLY,
    TimeInterval.YEARLY,
];

interface IProps {
    /** Pass classNames to the component root */
    className?: string;
    /** Used to set the time interval */
    onChange(interval: TimeInterval): void;
    /** The currently selected date range */
    range: IDateModifierRange;
    /** The currently selected interval */
    interval: TimeInterval;
}

/**
 * This component renders a radio group of time intervals and disables selections by
 * the range passed in as a prop
 */
export function IntervalPicker(props: IProps) {
    const { className, onChange, range, interval } = props;

    const isInRange = (value: number, min: number, max: number): boolean => value >= min && value <= max;

    /**
     * This keeps record of the optimized intervals for a given range.
     * If there are multiple optimized intervals for a range.
     */
    const optimizedIntervals = useMemo<TimeInterval[]>(() => {
        const { start, end } = timeFrameFromDateModifierRange(range);
        // This is the number of days between start and end
        const dayDelta = Math.abs(moment(start).diff(moment(end).endOf("day"), "days"));

        // Less than 1 day
        if (isInRange(dayDelta, 0, 1)) {
            return [TimeInterval.HOURLY];
        }
        // Between 1 day and 2 weeks
        if (isInRange(dayDelta, 2, 13)) {
            return [TimeInterval.DAILY];
        }
        // Between 2 weeks and 3 months
        if (isInRange(dayDelta, 14, 90)) {
            return [TimeInterval.WEEKLY, TimeInterval.DAILY, TimeInterval.MONTHLY];
        }
        // Between 3 months and 1 year
        if (isInRange(dayDelta, 91, 365)) {
            return [TimeInterval.MONTHLY, TimeInterval.WEEKLY];
        }
        // Between 1 year and 2 years
        if (isInRange(dayDelta, 366, 730)) {
            return [TimeInterval.MONTHLY];
        }
        // More than 2 years
        if (isInRange(dayDelta, 731, Infinity)) {
            return [TimeInterval.YEARLY];
        }

        return [];
    }, [range]);

    const wrapper = css({
        "* + &": {
            margin: "0 0 16px",
        },
    });

    return (
        <section className={className}>
            <RadioGroup
                classes={searchInFilterClasses()}
                buttonClass={buttonClasses().radio}
                buttonActiveClass={buttonClasses().radio}
                activeItem={interval}
                setData={onChange}
            >
                {intervals.map((interval) => (
                    <RadioInputAsButton key={interval} label={labelize(t(interval))} data={interval} />
                ))}
            </RadioGroup>
            {!optimizedIntervals.includes(interval) && (
                <Message
                    className={wrapper}
                    type="info"
                    icon={<Icon icon={"data-information"} />}
                    stringContents={t(
                        "Exportable data available, but charts may not be visually optimized for this time and date range.",
                    )}
                />
            )}
        </section>
    );
}
