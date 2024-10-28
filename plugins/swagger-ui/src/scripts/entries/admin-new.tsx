import { RouterRegistry } from "@library/Router.registry";
import RouteHandler from "@library/routing/RouteHandler";

const Route = new RouteHandler(
    () => import("../ApiOverviewPage"),
    "/settings/api-docs",
    () => "/settings/api-docs",
);

RouterRegistry.addRoutes([Route.route]);
