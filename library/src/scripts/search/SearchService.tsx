/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ISearchSource } from "@library/search/searchTypes";

export class SearchService {
    private static _supportsExtensions = false;
    static setSupportsExtensions(supports: boolean) {
        this._supportsExtensions = supports;
    }
    static supportsExtensions(): boolean {
        return this._supportsExtensions;
    }

    static sources = [] as ISearchSource[];

    static addSource = function (source: ISearchSource) {
        if (!SearchService.sources.find((content) => content.key === source.key)) {
            SearchService.sources.push(source);
        }
    };
}
