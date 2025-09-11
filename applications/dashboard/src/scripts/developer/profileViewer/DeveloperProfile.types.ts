/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

interface IBaseSpan {
    uuid: string;
    parentUuid: string;
    startMs: number;
    endMs: number;
    elapsedMs: number;
}

export interface IDeveloperProfileCacheReadSpan extends IBaseSpan {
    type: "cacheRead";
    data: {
        keys: string[];
        hitCount: number;
    };
}

export interface IDeveloperProfileCacheWriteSpan extends IBaseSpan {
    type: "cacheRead";
    data: {
        keys: string[];
    };
}

export interface IDeveloperProfileDbSpan extends IBaseSpan {
    type: "dbRead" | "dbWrite";
    data: {
        query: string;
        params: Record<string, any>;
    };
}

export interface IDeveloperProfileRequestSpan extends IBaseSpan {
    type: "request";
    data: {
        method: string;
        url: string;
        query: Record<string, any>;
    };
}

export interface IDeveloperProfileGenericSpan extends IBaseSpan {
    type: string;
    data: any;
}

export type IDeveloperProfileSpan =
    | IDeveloperProfileRequestSpan
    | IDeveloperProfileDbSpan
    | IDeveloperProfileCacheReadSpan
    | IDeveloperProfileCacheWriteSpan
    | IDeveloperProfileGenericSpan;

export interface IDeveloperProfile {
    developerProfileID: number;
    dateRecorded: string;
    timers: Record<string, number>;
    requestID: string;
    requestElapsedMs: number;
    requestMethod: string;
    requestPath: string;
    requestQuery: Record<string, any>;
    isTracked: boolean;
    name: string;
}

export interface IDeveloperProfileDetails extends IDeveloperProfile {
    profile: {
        spans: Record<string, IDeveloperProfileSpan>;
        rootSpanUuid: string;
    };
}
