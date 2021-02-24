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
import { DiscussionsListModule } from "@library/features/discussions/DiscussionListModule";

registerReducer("forum", forumReducer);
registerCommunitySearchDomain();

addComponent("HomeWidget", HomeWidget, { overwrite: true });
addComponent("DiscussionListModule", DiscussionsListModule, { overwrite: true });
addComponent("QuickLinks", QuickLinks, { overwrite: true });
addComponent("CallToAction", CallToAction, { overwrite: true });

SearchContextProvider.setOptionProvider(new CommunitySearchProvider());
accessibleRoleButton();
