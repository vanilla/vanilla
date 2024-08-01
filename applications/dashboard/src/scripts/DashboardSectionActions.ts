/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import apiv2 from "@library/apiv2";
import { createAsyncThunk } from "@reduxjs/toolkit";

export const fetchDashboardSections = createAsyncThunk("@@dashboardsections/fetchDashboardSections", async () => {
    const response = await apiv2.get("/dashboard/menus", {});
    return response.data;
});
