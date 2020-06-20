/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { registerReducer } from "@library/redux/reducerRegistry";
import { forumReducer } from "@vanilla/addon-vanilla/redux/reducer";
import { registerCommunitySearchDomain } from "@vanilla/addon-vanilla/search/registerCommunitySearchDomain";

registerReducer("forum", forumReducer);
registerCommunitySearchDomain();
