/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LayoutOverviewRoute } from "@dashboard/appearance/routes/appearanceRoutes";
import { useLayoutQuery } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { getRelativeUrl } from "@library/utility/appUtils";
import React from "react";
import { Redirect, RouteComponentProps } from "react-router-dom";

export default function LayoutOverviewRedirectPage(
    props: RouteComponentProps<{
        layoutID: string;
    }>,
) {
    const layoutQuery = useLayoutQuery(props.match.params.layoutID);

    // fixme: show a loader while we wait
    return layoutQuery.data ? <Redirect to={getRelativeUrl(LayoutOverviewRoute.url(layoutQuery.data!))} /> : <></>;
}
