/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useEffect, FC } from "react";
import { ViewType } from "@library/analytics/tracking";

interface IProps {
    data?: Record<string, any>;
    uniqueKey: string | number; // A new analytics event is only fired when this changes.
}

/**
 * A component to trigger an analytics event. The unique key must change between renders for a new event to fire.
 */
export const AnalyticsData: FC<IProps> = (props: IProps) => {
    const { data, uniqueKey } = props;

    useEffect(() => {
        let detail = data?.layoutViewType ? null : data;

        switch (data?.layoutViewType) {
            case "post":
                detail = {
                    type: ViewType.DISCUSSION,
                    discussionID: data.recordID,
                };
                break;
            case "article":
                detail = {
                    type: ViewType.KB_ARTICLE,
                    articleID: parseInt(data.recordID),
                };
                break;
            case "knowledgeCategory":
                detail = {
                    type: ViewType.KB_CATEGORY,
                    knowledgeCategoryID: parseInt(data.recordID),
                };
                break;
            // TODO add handling for KB_DEFAULT when available
            // KB home: https://higherlogic.atlassian.net/browse/VNLA-6724
            // Help center home: https://higherlogic.atlassian.net/browse/VNLA-6726
            default:
                detail = data;
        }

        document.dispatchEvent(new CustomEvent("pageViewWithContext", { detail }));

        // this one is for soft page loads/navigation, also dispatched on hard page loads for legacy pages in appUtils -> exec()
        document.dispatchEvent(new CustomEvent("X-PageView", { bubbles: true, cancelable: false }));

        // Setting data here will cause the analytics event to fired far to often.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [uniqueKey]);
    return <>{null}</>;
};

export const onPageViewWithContext = (callback: EventListenerOrEventListenerObject) => {
    document.addEventListener("pageViewWithContext", callback);
};
