/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { LayoutOverviewRoute } from "@dashboard/appearance/routes/appearanceRoutes";
import { useLayout } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { getRelativeUrl } from "@library/utility/appUtils";
import { Redirect, RouteComponentProps } from "react-router-dom";

export default function LayoutOverviewRedirectPage(
    props: RouteComponentProps<{
        layoutID: string;
    }>,
) {
    const layoutLoadable = useLayout(props.match.params.layoutID);

    // fixme: show a loader while we wait
    return layoutLoadable?.data ? (
        <Redirect to={getRelativeUrl(LayoutOverviewRoute.url(layoutLoadable.data!))} />
    ) : (
        <></>
    );
}
