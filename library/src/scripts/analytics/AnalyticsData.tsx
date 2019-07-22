/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { FC, useEffect } from "react";

interface IProps {
    data: object;
}

export const AnalyticsData: FC<IProps> = props => {
    const { data } = props;

    useEffect(() => {
        document.dispatchEvent(new CustomEvent("pageViewWithContext", { detail: { data } }));
    }, [props.data]);
    return null;
};

export const onPageViewWithContext = (callback: EventListenerOrEventListenerObject) => {
    document.addEventListener("pageViewWithContext", callback);
};
