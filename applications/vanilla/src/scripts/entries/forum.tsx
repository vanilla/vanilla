/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { registerReducer } from "@library/redux/reducerRegistry";
import { forumReducer } from "@vanilla/addon-vanilla/redux/reducer";
import { registerCommunitySearchDomain } from "@vanilla/addon-vanilla/search/registerCommunitySearchDomain";
import { SearchContextProvider } from "@library/contexts/SearchContext";
import { CommunitySearchProvider } from "@vanilla/addon-vanilla/search/CommunitySearchProvider";
import { accessibleRoleButton } from "@vanilla/addon-vanilla/legacy/legacyAccessibility";
import { addComponent } from "@library/utility/componentRegistry";
import { HomeWidget } from "@library/homeWidget/HomeWidget";
import { QuickLinks } from "@library/navigation/QuickLinks";
import { CallToAction } from "@library/callToAction/CallToAction";
import { DiscussionListModule } from "@library/features/discussions/DiscussionListModule";
import { UserSpotlight } from "@library/userSpotlight/UserSpotlight";
import { SearchWidget } from "@library/searchWidget/SearchWidget";
import { CategoryPicker } from "@library/forms/select/CategoryPicker";
import { CategoryFollowDropDown } from "@vanilla/addon-vanilla/categories/CategoryFollowDropdown";
import TabWidget from "@library/tabWidget/TabWidget";
import NewPostMenu from "@library/newPostMenu/NewPostMenu";
import { triggerLegacyHashScrolling } from "@vanilla/addon-vanilla/legacy/legacyHashScrolling";
import "@vanilla/addon-vanilla/forms/autosave";
import { LeaderboardWidget } from "@library/leaderboardWidget/LeaderboardWidget";
import { CategoriesWidget } from "@library/categoriesWidget/CategoriesWidget";
import { RSSWidget } from "@library/rssWidget/RSSWidget";
import { UserSpotlightWidget } from "@library/userSpotlight/UserSpotlightWidget";
import { DiscussionsWidget } from "@library/features/discussions/DiscussionsWidget";
import CallToActionWidget from "@library/callToAction/CallToActionWidget";
import { onReady } from "@library/utility/appUtils";
import { AboutMeWidget } from "@library/aboutMeWidget/AboutMeWidget";
import { ProfileOverviewWidget } from "@library/profileOverviewWidget/ProfileOverviewWidget";
import { ProfileAnalyticsWidget } from "@library/profileAnalyticsWidget/ProfileAnalyticsWidget";
import { AccountSettings } from "@library/accountSettings/AccountSettings";
import { EditProfileFields } from "@library/editProfileFields/EditProfileFields";
import { delegateEvent } from "@vanilla/dom-utils";
import { mountModal } from "@library/modal/mountModal";
import { CollectionRecordTypes } from "@library/featuredCollections/Collections.variables";
import React from "react";
import { CollectionsOptionButton } from "@library/featuredCollections/CollectionsOptionButton";

registerReducer("forum", forumReducer);
registerCommunitySearchDomain();

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

SearchContextProvider.setOptionProvider(new CommunitySearchProvider());
accessibleRoleButton();
onReady(() => {
    triggerLegacyHashScrolling();
});

delegateEvent("click", ".js-addDiscussionToCollection", (event, triggeringElement) => {
    event.preventDefault();
    const discussionID = triggeringElement.getAttribute("data-discussionID") || null;
    const recordType = triggeringElement.getAttribute("data-recordType");

    if (discussionID === null) {
        return;
    }
    const id = parseInt(discussionID, 10);
    mountModal(
        <CollectionsOptionButton
            initialVisibility={true}
            recordID={id}
            recordType={recordType as CollectionRecordTypes}
            modalOnly
        />,
    );
});
