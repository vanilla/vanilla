/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import apiv2 from "@library/apiv2";
import { IHydratedLayoutSpec, ILayoutQuery } from "@library/features/Layout/LayoutRenderer.types";
import { createAsyncThunk } from "@reduxjs/toolkit";
import { getCurrentLocale } from "@vanilla/i18n";

export const lookupLayout = createAsyncThunk("@@layouts/lookup", async (query: ILayoutQuery) => {
    const layoutSpec = await apiv2.get<IHydratedLayoutSpec>("/layouts/lookup-hydrate", {
        params: query,
    });
    return layoutSpec.data;
});
