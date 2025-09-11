import { RecordID, logError } from "@vanilla/utils";
import { getMeta, getSiteSection, siteUrl } from "@library/utility/appUtils";

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import apiv2 from "@library/apiv2";
import { delegateEvent } from "@vanilla/dom-utils";

export enum ViewType {
    DEFAULT = "page_view",
    DISCUSSION = "discussion_view",
    KB_DEFAULT = "kb_view",
    KB_ARTICLE = "kb_article_view",
    KB_CATEGORY = "kb_category_view",
    EXTERNAL_NAVIGATION = "externalNavigation",
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
    const referrer: string = document.referrer;
    const groupID: RecordID = getMeta("groupID");
    const eventID: RecordID = getMeta("eventID");
    const pageName: string = document.title;

    // TickExtra could contain the categoryID
    const categoryID = tickExtra?.CategoryID;

    const pageViewContext = {
        url,
        pageName,
        ...(discussionID && { discussionID }),
        ...(categoryID && { categoryID }),
        ...(siteSectionID && { siteSectionID }),
        ...(referrer && { referrer }),
        ...(groupID && { groupID }),
        ...(eventID && { eventID }),
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

/**
 * Track link clicks to external sites
 */
export const trackLink = () => delegateEvent("click", "a", trackExternalNavigation);
export const trackExternalNavigation = (event, triggeringElement) => {
    const url = new URL(window.location.href);
    let destinationUrl = triggeringElement.getAttribute("href");

    // Check if the destination URL is external and not on the leaving page
    const destination = new URL(siteUrl(destinationUrl));
    const isExternal =
        url.pathname !== "/home/leaving" &&
        (destination.origin !== siteUrl("") || destination.pathname == "/home/leaving");

    // If the destination URL is external, track the external navigation
    if (isExternal) {
        trackEvent(ViewType.EXTERNAL_NAVIGATION, {
            url,
            destinationUrl: destination.searchParams.get("target") ?? destinationUrl,
            siteSectionID: getSiteSection()?.sectionID,
        });
    }
};
