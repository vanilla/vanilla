/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DiscussionsApi } from "@vanilla/addon-vanilla/posts/DiscussionsApi";

export function makeMockDiscussionsApi(): typeof DiscussionsApi {
    const fakeApi = {};
    for (const key in DiscussionsApi) {
        fakeApi[key] = vitest.fn(async () => {
            return {
                name: `{key} response`,
            };
        });
    }

    return fakeApi as typeof DiscussionsApi;
}
