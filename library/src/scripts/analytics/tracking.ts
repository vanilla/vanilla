/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import { logError } from "@vanilla/utils";
import apiv2 from "@library/apiv2";
import { getMeta, getSiteSection, onReady } from "@library/utility/appUtils";
import { RecordID } from "@vanilla/utils";

export enum ViewType {
    DEFAULT = "page_view",
    DISCUSSION = "discussion_view",
    KB_DEFAULT = "kb_view",
    KB_ARTICLE = "kb_article_view",
    KB_CATEGORY = "kb_category_view",
}

/**
 * This function will track page views via the `/tick` endpoint
 * @param url - Defaults to `window.location.href`
 * @param context - Will contain at minimum a `type` and optionally `discussionID` from the siteMeta
 * @param viewType - Defaults to the viewEventType from the siteMeta
 */
export const trackPageView = (url: string = window.location.href, context?: object, viewType?: ViewType) => {
    const viewEventType: ViewType = viewType ? viewType : getMeta("viewEventType") ?? ViewType.DEFAULT;
    const discussionID: RecordID = getMeta("DiscussionID");
    const tickExtra: Record<string, any> = JSON.parse(getMeta("TickExtra", "{}"));
    const siteSectionID: RecordID = getSiteSection()?.sectionID;

    // TickExtra could contain the categoryID
    const categoryID = tickExtra?.CategoryID;

    const pageViewContext = {
        url,
        ...(discussionID && { discussionID }),
        ...(categoryID && { categoryID }),
        ...(siteSectionID && { siteSectionID }),
        ...(context && { ...context }),
    };

    trackEvent(viewEventType, pageViewContext);
};

/**
 * This function will fire a call to the `/tick` endpoint
 * with the arguments provided as the post body
 */
export const trackEvent = (viewType: ViewType, data: object) => {
    apiv2.post("/tick", { type: viewType, ...data }).catch((error) => logError(error));
};
