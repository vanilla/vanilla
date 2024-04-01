/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Represent pages potentially returned from a Link header.
 */
export interface ILinkPages {
    next?: number;
    prev?: number;
    limit?: number;
    total?: number;
    currentPage?: number;
    nextURL?: string;
    prevURL?: string;
    currentResultsLength?: number;
}

export interface IWithPaging<T> {
    paging: ILinkPages;
    data: T;
}

/**
 * @deprecated Use {@link IWithPaging}
 */
export interface IWithPagination<T> {
    pagination: ILinkPages;
    body: T;
}

export default class SimplePagerModel {
    public static parseHeaders(headers?: Record<string, string>): ILinkPages {
        headers = headers ?? {};
        const result: ILinkPages = {};

        if ("x-app-page-next-url" in headers) {
            result.nextURL = headers["x-app-page-next-url"];
        }

        if ("x-app-page-prev-url" in headers) {
            result.prevURL = headers["x-app-page-prev-url"];
        }

        if ("x-app-page-result-count" in headers) {
            result.total = parseInt(headers["x-app-page-result-count"]);
        }

        if ("x-app-page-current" in headers) {
            result.currentPage = parseInt(headers["x-app-page-current"]);
            if (result.nextURL) {
                result.next = result.currentPage + 1;
            }

            if (result.prevURL) {
                result.prev = result.currentPage - 1;
            }
        }

        if ("x-app-page-limit" in headers) {
            result.limit = parseInt(headers["x-app-page-limit"]);
        }

        return result;
    }
}
