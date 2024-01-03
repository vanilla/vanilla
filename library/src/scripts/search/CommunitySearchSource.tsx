/**
 * @author Mihran Abrahamian <mabrahamian@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import apiv2 from "@library/apiv2";
import SimplePagerModel from "@library/navigation/SimplePagerModel";
import { ISearchRequestQuery, ISearchResult, ISearchSource } from "@library/search/searchTypes";
import { t } from "@vanilla/i18n";
import type SearchDomain from "@library/search/SearchDomain";
import { IResult } from "@library/result/Result";
import { SearchDomainLoadable } from "@library/search/SearchDomainLoadable";

class CommunitySearchSource implements ISearchSource {
    private abortController: AbortController;

    constructor() {
        this.abortController = new AbortController();
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

    private asyncDomains: SearchDomainLoadable[] = [];

    private loadedDomains: SearchDomain[] = [];

    public addDomain = (loadable: SearchDomainLoadable) => {
        this.asyncDomains.push(loadable);
    };

    get domains(): SearchDomain[] {
        return this.loadedDomains;
    }

    public clearDomains() {
        this.loadedDomains = [];
        this.asyncDomains = [];
    }

    public loadDomains = async (): Promise<SearchDomain[]> => {
        for (let asyncDomain of this.asyncDomains) {
            if (asyncDomain.loadedDomain) {
                this.pushDomain(asyncDomain.loadedDomain);
            } else {
                const loaded = await asyncDomain.load();
                this.pushDomain(loaded);
            }
        }
        return this.loadedDomains;
    };

    private pushDomain(domain: SearchDomain) {
        if (!this.loadedDomains.find(({ key }) => key === domain.key)) {
            this.loadedDomains.push(domain);
        }
    }
}

const COMMUNITY_SEARCH_SOURCE = new CommunitySearchSource();

export default COMMUNITY_SEARCH_SOURCE;
