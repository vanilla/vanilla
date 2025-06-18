/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import AdminHeader from "@dashboard/components/AdminHeader";
import Loader from "@library/loaders/Loader";
import PageLoader from "@library/routing/PageLoader";
import RouteHandler from "@library/routing/RouteHandler";

function StaffRoutePlaceholder() {
    return (
        <>
            <AdminHeader />
            <Loader />
        </>
    );
}

export const DeveloperProfileListRoute = new RouteHandler(
    () => import("@dashboard/developer/pages/DeveloperProfileListPage"),
    "/settings/vanilla-staff/profiles",
    () => "/settings/vanilla-staff/profiles",
    StaffRoutePlaceholder,
);

export const DeveloperProfileDetailRoute = new RouteHandler(
    () => import("@dashboard/developer/pages/DeveloperProfileDetailPage"),
    "/settings/vanilla-staff/profiles/:profileID",
    (profileID: number) => `/settings/vanilla-staff/profiles/${profileID}`,
    StaffRoutePlaceholder,
);

export const ProductMessagesRoute = new RouteHandler(
    () => import("@library/features/adminAssistant/pages/ProductMessagesListPage"),
    "/settings/vanilla-staff/product-messages",
    () => `/settings/vanilla-staff/product-messages`,
    StaffRoutePlaceholder,
);

export const ProductMessagesAddEditRoute = new RouteHandler(
    () => import("@library/features/adminAssistant/pages/ProductMessageAddEditPage"),
    ["/settings/vanilla-staff/product-messages/add", "/settings/vanilla-staff/product-messages/:productMessageID/edit"],
    (productMessageID?: string) =>
        productMessageID
            ? `/settings/vanilla-staff/product-messages/${productMessageID}/edit`
            : "/settings/vanilla-staff/product-messages/add",
    StaffRoutePlaceholder,
);

export function getVanillaStaffRoutes() {
    return [
        DeveloperProfileListRoute.route,
        DeveloperProfileDetailRoute.route,
        ProductMessagesAddEditRoute.route,
        ProductMessagesRoute.route,
    ];
}
