/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { SearchDomainLoadable } from "@library/search/SearchDomainLoadable";

const DISCUSSIONS_SEARCH_DOMAIN = new SearchDomainLoadable(
    "discussions",
    () => import(/* webpackChunkName: "searchDomain/discussions" */ "./DiscussionsSearchDomain.loadable"),
);

export default DISCUSSIONS_SEARCH_DOMAIN;
