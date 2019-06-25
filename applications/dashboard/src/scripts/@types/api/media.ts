/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export interface IScrapeData {
    embedType: string;
    type: string;
    url: string;
    name?: string;
    body?: string | null;
    photoUrl?: string | null;
    height?: number | null;
    width?: number | null;
    attributes: {
        [key: string]: any;
    };
}
