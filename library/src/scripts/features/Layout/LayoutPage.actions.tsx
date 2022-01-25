/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import apiv2 from "@library/apiv2";
import { ILayoutQuery, ILayoutSpec } from "@library/features/Layout/LayoutPage.types";
import { getSiteSection } from "@library/utility/appUtils";
import { createAsyncThunk } from "@reduxjs/toolkit";

/**
 * Lookup a layout. In the future this will use a lookup API.
 * Right now for testing purposes it uses the hardcoded layoutID 1 which is a home layout.
 * That can be edited at /settings/layout/playground
 */
export const lookupLayout = createAsyncThunk("@@layouts/lookup", async (query?: ILayoutQuery) => {
    const layoutSpec = await apiv2.get("/layouts/1/hydrate", {
        params: {
            params: {
                siteSectionID: getSiteSection().sectionID,
            },
        },
    });
    return layoutSpec.data as ILayoutSpec;
});
