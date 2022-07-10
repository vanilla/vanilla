/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import apiv2 from "@library/apiv2";
import { IHydratedLayoutSpec, ILayoutQuery } from "@library/features/Layout/LayoutRenderer.types";
import { getSiteSection } from "@library/utility/appUtils";
import { createAsyncThunk } from "@reduxjs/toolkit";
import { getCurrentLocale } from "@vanilla/i18n";

export const lookupLayout = createAsyncThunk("@@layouts/lookup", async (query?: ILayoutQuery) => {
    const layoutSpec = await apiv2.get("/layouts/lookup-hydrate", {
        params: {
            layoutViewType: query?.layoutViewType,
            recordID: query?.recordID,
            recordType: query?.recordType,
            params: {
                siteSectionID: getSiteSection().sectionID,
                locale: getCurrentLocale(),
            },
        },
    });
    return layoutSpec.data as IHydratedLayoutSpec;
});
