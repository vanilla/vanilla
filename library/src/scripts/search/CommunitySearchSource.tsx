/**
 * @author Mihran Abrahamian <mabrahamian@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import apiv2 from "@library/apiv2";
import SimplePagerModel from "@library/navigation/SimplePagerModel";
import { ISearchRequestQuery, ISearchResult, ISearchSource } from "@library/search/searchTypes";
import { t } from "@vanilla/i18n";
import SearchDomain from "@library/search/SearchDomain";
import { IResult } from "@library/result/Result";

class CommunitySearchSource implements ISearchSource {
    private abortController: AbortController;

    constructor() {
        this.abortController = new AbortController();
        this.domains = [];
    }

    abort() {
        this.abortController.abort();
        this.abortController = new AbortController();
    }

    get key() {
        return "community";
    }

    get label() {
        return t("Community");
    }

    async performSearch(query: ISearchRequestQuery) {
        const response = await apiv2.get("/search", {
            params: query,
            signal: this.abortController.signal,
        });
        return {
            results: response.data.map((item) => {
                item.body = item.excerpt ?? item.body;
                return item;
            }),
            pagination: SimplePagerModel.parseHeaders(response.headers),
        };
    }

    public domains: ISearchSource["domains"];

    addDomain<ExtraFormValues extends object, ResultType extends ISearchResult, ResultComponentProps extends IResult>(
        domain: SearchDomain<ExtraFormValues, ResultType, ResultComponentProps>,
    ) {
        if (!this.domains.find(({ key }) => key === domain.key)) {
            this.domains.push(domain);
        }
    }
}

const COMMUNITY_SEARCH_SOURCE = new CommunitySearchSource();

export default COMMUNITY_SEARCH_SOURCE;
