/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { SearchDomainLoadable } from "@library/search/SearchDomainLoadable";

const MEMBERS_SEARCH_DOMAIN = new SearchDomainLoadable(
    "members",
    () => import(/* webpackChunkName: "searchDomain/members" */ "./MembersSearchDomain.loadable"),
);

export default MEMBERS_SEARCH_DOMAIN;
