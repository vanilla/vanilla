/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LegacyLayoutsRoute } from "@dashboard/appearance/routes/appearanceRoutes";
import { getRelativeUrl } from "@library/utility/appUtils";
import React from "react";
import { Redirect } from "react-router-dom";

export default function AppearanceLayoutsPage() {
    return <Redirect to={getRelativeUrl(LegacyLayoutsRoute.url("home"))} />;
}
