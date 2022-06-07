/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { registerLayoutPage } from "@library/features/Layout/LayoutPage";

registerLayoutPage("/discussions", (params) => {
    return {
        layoutViewType: "discussionList",
        params,
    };
});
