/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { AboutMeWidget } from "@library/aboutMeWidget/AboutMeWidget";
import { AccountSettings } from "@library/accountSettings/AccountSettings";
import { CallToAction } from "@library/widgets/CallToAction";
import CallToActionWidget from "@library/widgets/CallToActionWidget";
import { CategoriesWidget } from "@library/categoriesWidget/CategoriesWidget";
import { SearchContextProvider } from "@library/contexts/SearchContext";
import { EditProfileFields } from "@library/editProfileFields/EditProfileFields";
import { CollectionRecordTypes } from "@library/featuredCollections/Collections.variables";
import { CollectionsOptionButton } from "@library/featuredCollections/CollectionsOptionButton";
import { DiscussionListModule } from "@library/features/discussions/DiscussionListModule";
import { DiscussionsWidget } from "@library/features/discussions/DiscussionsWidget";
import FollowedContent from "@library/followedContent/FollowedContent";
import { CategoryPicker } from "@library/forms/select/CategoryPicker";
import { HomeWidget } from "@library/homeWidget/HomeWidget";
import { LeaderboardWidget } from "@library/leaderboardWidget/LeaderboardWidget";
import { mountModal } from "@library/modal/mountModal";
import { QuickLinks } from "@library/navigation/QuickLinks";
import NewPostMenu from "@library/newPostMenu/NewPostMenu";
import NotificationPreferences from "@library/notificationPreferences";
import { ProfileAnalyticsWidget } from "@library/profileAnalyticsWidget/ProfileAnalyticsWidget";
import { ProfileOverviewWidget } from "@library/profileOverviewWidget/ProfileOverviewWidget";
import { registerReducer } from "@library/redux/reducerRegistry";
import { RouterRegistry } from "@library/Router.registry";
import { RSSWidget } from "@library/rssWidget/RSSWidget";
import { SearchWidget } from "@library/searchWidget/SearchWidget";
import TabWidget from "@library/tabWidget/TabWidget";
import { UnsubscribePageRoute } from "@library/unsubscribe/unsubscribePageRoutes";
import { UserSpotlight } from "@library/userSpotlight/UserSpotlight";
import { UserSpotlightWidget } from "@library/userSpotlight/UserSpotlightWidget";
import { onReady } from "@library/utility/appUtils";
import { addComponent } from "@library/utility/componentRegistry";
import CategoryFollowDropDown from "@vanilla/addon-vanilla/categories/CategoryFollowDropdown";
import "@vanilla/addon-vanilla/forms/autosave";
import { accessibleRoleButton } from "@vanilla/addon-vanilla/legacy/legacyAccessibility";
import { triggerLegacyHashScrolling } from "@vanilla/addon-vanilla/legacy/legacyHashScrolling";
import { forumReducer } from "@vanilla/addon-vanilla/redux/reducer";
import { CommunitySearchProvider } from "@vanilla/addon-vanilla/search/CommunitySearchProvider";
import { delegateEvent } from "@vanilla/dom-utils";
import { LegacyFlagDropdown } from "@library/postReactions/LegacyFlagDropdown";
import {
    LegacyIntegrationsOptionsMenuItems,
    LegacyThreadAttachmentsAsset,
} from "@vanilla/addon-vanilla/legacy/LegacyAttachments";
import { TrollComment } from "@vanilla/addon-vanilla/legacy/LegacyTrollComment";
import { LegacyPostMetaAsset } from "@vanilla/addon-vanilla/legacy/LegacyPostMetaAsset";
import { DraftsPageRoute } from "@vanilla/addon-vanilla/drafts/DraftsRoutes";
import DiscussionOptionsPostSettings from "@library/features/discussions/DiscussionOptionsPostSettings";
import { useDiscussionQuery } from "@library/features/discussions/discussionHooks";

registerReducer("forum", forumReducer);

addComponent("HomeWidget", HomeWidget, { overwrite: true });
addComponent("DiscussionListModule", DiscussionListModule, { overwrite: true });
addComponent("DiscussionDiscussionsWidget", DiscussionsWidget, { overwrite: true });
addComponent("DiscussionAnnouncementsWidget", DiscussionsWidget, { overwrite: true });
addComponent("QuickLinks", QuickLinks, { overwrite: true });
addComponent("CallToAction", CallToAction, { overwrite: true });
addComponent("UserSpotlight", UserSpotlight, { overwrite: true });
addComponent("SearchWidget", SearchWidget, { overwrite: true });
addComponent("CategoryPicker", CategoryPicker, { overwrite: true });
addComponent("CategoryFollowDropDown", CategoryFollowDropDown, { overwrite: true });
addComponent("TabWidget", TabWidget, { overwrite: true });
addComponent("NewPostMenu", NewPostMenu, { overwrite: true });
addComponent("LeaderboardWidget", LeaderboardWidget, { overwrite: true });
addComponent("CategoriesWidget", CategoriesWidget, { overwrite: true });
addComponent("RSSWidget", RSSWidget, { overwrite: true });
addComponent("UserSpotlightWidget", UserSpotlightWidget, { overwrite: true });
addComponent("CallToActionWidget", CallToActionWidget, { overwrite: true });
addComponent("AboutMeWidget", AboutMeWidget, { overwrite: true });
addComponent("ProfileOverviewWidget", ProfileOverviewWidget, { overwrite: true });
addComponent("ProfileAnalyticsWidget", ProfileAnalyticsWidget, { overwrite: true });
addComponent("AccountSettings", AccountSettings, { overwrite: true });
addComponent("EditProfileFields", EditProfileFields, { overwrite: true });
addComponent("FollowedContent", FollowedContent, { overwrite: true });
addComponent("NotificationPreferences", NotificationPreferences, { overwrite: true });
addComponent("LegacyThreadAttachmentsAsset", LegacyThreadAttachmentsAsset);
addComponent("LegacyIntegrationsOptionsMenuItems", LegacyIntegrationsOptionsMenuItems);
addComponent("TrollComment", TrollComment);
addComponent("LegacyFlagDropdown", LegacyFlagDropdown);
addComponent("legacyPostMeta", LegacyPostMetaAsset);

SearchContextProvider.setOptionProvider(new CommunitySearchProvider());
accessibleRoleButton();
onReady(() => {
    triggerLegacyHashScrolling();
});

RouterRegistry.addRoutes([UnsubscribePageRoute.route, DraftsPageRoute.route]);

delegateEvent("click", ".js-addDiscussionToCollection", (event, triggeringElement) => {
    event.preventDefault();
    const discussionID = triggeringElement.getAttribute("data-discussionID") || null;
    const recordType = triggeringElement.getAttribute("data-recordType");

    if (discussionID === null) {
        return;
    }
    const id = parseInt(discussionID, 10);
    void mountModal(
        <CollectionsOptionButton
            initialVisibility={true}
            recordID={id}
            recordType={recordType as CollectionRecordTypes}
            modalOnly
        />,
    );
});

delegateEvent("click", ".js-postSettingsMove", (event, triggeringElement) => {
    event.preventDefault();
    const discussionID = triggeringElement.getAttribute("data-discussionID") || null;

    if (discussionID === null) {
        return;
    }

    void mountModal(<DiscussionFetch discussionID={discussionID} initialAction={"move"} />);
});

delegateEvent("click", ".js-postSettingsChange", (event, triggeringElement) => {
    event.preventDefault();
    const discussionID = triggeringElement.getAttribute("data-discussionID") || null;

    if (discussionID === null) {
        return;
    }

    void mountModal(<DiscussionFetch discussionID={discussionID} initialAction={"change"} />);
});

/**
 * Wrapper component to fetch discussion data and render PostSettingsModal
 */
function DiscussionFetch({
    discussionID,
    initialAction,
}: {
    discussionID: string;
    initialAction?: "move" | "change" | null;
}) {
    const { data: discussion, isLoading } = useDiscussionQuery(discussionID);

    if (isLoading || !discussion) {
        return <></>;
    }

    return <DiscussionOptionsPostSettings discussion={discussion} initialAction={initialAction} modalOnly />;
}
