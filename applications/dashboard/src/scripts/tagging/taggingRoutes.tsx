/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import Loader from "@library/loaders/Loader";
import RouteHandler from "@library/routing/RouteHandler";

const TaggingIndex = new RouteHandler(
    () => import("@dashboard/tagging/pages/TaggingSettings"),
    "/settings/tagging",
    () => "/settings/tagging",
    Loader,
);

export function getTaggingSettingsRoutes() {
    return [TaggingIndex.route];
}
