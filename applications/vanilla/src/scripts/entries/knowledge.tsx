/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { registerReducer } from "@library/redux/reducerRegistry";
import COMMUNITY_SEARCH_SOURCE from "@library/search/CommunitySearchSource";
import { onReady } from "@library/utility/appUtils";
import { forumReducer } from "@vanilla/addon-vanilla/redux/reducer";
import DISCUSSIONS_SEARCH_DOMAIN from "../search/DiscussionsSearchDomain";

registerReducer("forum", forumReducer);
onReady(() => {
    COMMUNITY_SEARCH_SOURCE.addDomain(DISCUSSIONS_SEARCH_DOMAIN);
});
