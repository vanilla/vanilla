/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { BrandingPageRoute } from "@dashboard/appearance/routes/appearanceRoutes";
import { getRelativeUrl } from "@library/utility/appUtils";
import React from "react";
import { Redirect } from "react-router-dom";

export default function AppearancePage() {
    return <Redirect to={getRelativeUrl(BrandingPageRoute.url(null))} />;
}
