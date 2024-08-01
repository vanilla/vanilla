/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import COMMUNITY_SEARCH_SOURCE from "@library/search/CommunitySearchSource";
import { onReady } from "@library/utility/appUtils";
import DISCUSSIONS_SEARCH_DOMAIN from "@vanilla/addon-vanilla/search/DiscussionsSearchDomain";

onReady(() => {
    COMMUNITY_SEARCH_SOURCE.addDomain(DISCUSSIONS_SEARCH_DOMAIN);
});
