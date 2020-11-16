/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import qs from "qs";
import { ensureString } from "@vanilla/utils";

/**
 * Represent pages potentially returned from a Link header.
 */
export interface ILinkPages {
    next?: number;
    prev?: number;
    limit?: number;
    total?: number;
    currentPage?: number;
}

export interface IWithPagination<T> {
    pagination: ILinkPages;
    body: T;
}

export default class SimplePagerModel {
    public static parseHeaders(headers: Record<string, string>): ILinkPages {
        const result = this.parseLinkHeader(headers["link"], "page");

        if ("x-app-page-result-count" in headers) {
            result.total = parseInt(headers["x-app-page-result-count"]);
        }

        if ("x-app-page-current" in headers) {
            result.currentPage = parseInt(headers["x-app-page-current"]);
        }

        if ("x-app-page-limit" in headers) {
            result.limit = parseInt(headers["x-app-page-limit"]);
        }

        return result;
    }

    public static parseLinkHeader(header: string, param: string, limitParam?: string): ILinkPages {
        const result = {} as ILinkPages;

        header.split(",").map((link) => {
            link = link.trim();

            // Needs to fit our expected format.
            const parts = link.match(/<([0-9a-zA-Z$\-_.+!*'(),:/?=&%#]+)>;\s+rel="(next|prev)"/);
            if (!parts) {
                return;
            }

            // Extract the relevant bits.
            const [fullMatch, url, rel] = parts;

            // Confirm we have a query string.
            const search = url.match(/\?([^#]+)/);
            if (!search || !search[1]) {
                return;
            }

            // Break out the query string into individual parameters.
            const searchParameters = qs.parse(search[1]);

            // Grab the next or prev page number.
            if (searchParameters[param]) {
                result[rel] = parseInt(ensureString(searchParameters[param]), 10);
            }

            // Grab the limit from the current link.
            if (limitParam && searchParameters[limitParam]) {
                result.limit = parseInt(ensureString(searchParameters[param]), 10);
            }
        });

        return result;
    }
}
