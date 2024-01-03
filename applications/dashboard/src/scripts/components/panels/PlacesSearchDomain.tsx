/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { SearchDomainLoadable } from "@library/search/SearchDomainLoadable";

const PLACES_SEARCH_DOMAIN = new SearchDomainLoadable(
    "places",
    () => import(/* webpackChunkName: "searchDomain/places" */ "./PlacesSearchDomain.loadable"),
);
export default PLACES_SEARCH_DOMAIN;
