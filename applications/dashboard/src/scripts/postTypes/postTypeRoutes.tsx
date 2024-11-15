/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PostType } from "@dashboard/postTypes/postType.types";
import Loader from "@library/loaders/Loader";
import RouteHandler from "@library/routing/RouteHandler";

const PostTypeIndex = new RouteHandler(
    () => import("@dashboard/postTypes/pages/PostTypeSettings"),
    "/settings/post-types",
    () => "/settings/post-types",
    Loader,
);

const PostTypeAdd = new RouteHandler(
    () => import("@dashboard/postTypes/pages/PostTypeEdit"),
    "/settings/post-types/new",
    (postTypeID?: PostType["postTypeID"]) =>
        `/settings/post-types/new${postTypeID ? `?copy-post-type-id=${postTypeID}` : ""}`,
    Loader,
);

const PostTypeEdit = new RouteHandler(
    () => import("@dashboard/postTypes/pages/PostTypeEdit"),
    "/settings/post-types/edit/:postTypeID",
    (postTypeID: PostType["postTypeID"]) => "/settings/post-types/edit/:postTypeID",
    Loader,
);

export function getPostTypeSettingsRoutes() {
    return [PostTypeIndex.route, PostTypeEdit.route, PostTypeAdd.route];
}
