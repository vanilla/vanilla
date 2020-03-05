/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, FC } from "react";

interface IProps {
    data?: object;
    uniqueKey: string | number; // A new analytics event is only fired when this changes.
}

/**
 * A component to trigger an analytics event. The unique key must change between renders for a new event to fire.
 */
export const AnalyticsData: FC<IProps> = (props: IProps) => {
    const { data, uniqueKey } = props;

    useEffect(() => {
        document.dispatchEvent(new CustomEvent("pageViewWithContext", { detail: data }));
        // Setting data here will cause the analytics event to fired far to often.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [uniqueKey]);
    return <>{null}</>;
};

export const onPageViewWithContext = (callback: EventListenerOrEventListenerObject) => {
    document.addEventListener("pageViewWithContext", callback);
};
