/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { RouteComponentProps } from "react-router-dom";
import CategoriesLegacyLayoutsPage from "@dashboard/appearance/pages/CategoriesLegacyLayoutsPage";
import DiscussionsLegacyLayoutsPage from "@dashboard/appearance/pages/DiscussionsLegacyLayoutsPage";
import HomepageLegacyLayoutsPage from "@dashboard/appearance/pages/HomepageLegacyLayoutsPage";
import NotFoundPage from "@library/routing/NotFoundPage";

export default function LegacyLayoutsPage(
    props: RouteComponentProps<{
        layoutViewType: LayoutViewType;
    }>,
) {
    const { layoutViewType } = props.match.params;
    switch (layoutViewType) {
        case "home":
            return <HomepageLegacyLayoutsPage />;
        case "categoryList":
            return <CategoriesLegacyLayoutsPage />;
        case "discussionList":
            return <DiscussionsLegacyLayoutsPage />;
        default:
            return <NotFoundPage />;
    }
}
