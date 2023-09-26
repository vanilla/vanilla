/**
 * @author Mihran Abrahamian <mabrahamian@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { SearchService } from "@library/search/SearchService";
import COMMUNITY_SEARCH_SOURCE from "@library/search/CommunitySearchSource";

SearchService.addSource(COMMUNITY_SEARCH_SOURCE);
