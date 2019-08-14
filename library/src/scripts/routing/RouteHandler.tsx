/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { NavLink, NavLinkProps, Route } from "react-router-dom";
import { Omit } from "@library/@types/utils";
import Loadable, { LoadableComponent } from "react-loadable";
import Loader from "@library/loaders/Loader";
import { Hoverable } from "@vanilla/react-utils";

type LoadFunction = () => Promise<any>;

/**
 * Class for managing routing and matching a particular page.
 */
export default class RouteHandler<GeneratorProps> {
    /**
     * Local cache of react nodes caching a route.
     * This is used to allow multiple routes to share the same component instance if their key matches.
     *
     * Example:
     * <ResourceEditorPage /> has 2 allowed URL formats. /resource/:id/edit & /resource/editor
     *
     * We may want to change the URL from one 2 the other at some point of the lifecycle,
     * but we don't want the component to be umounted/remounted.
     *
     * In this case by passing the same `key` when constructing the route handler,
     * the same component instance will be re-used for both routes.
     */
    private static routeCache: { [key: string]: React.ReactNode } = {};

    /**
     * Clear the cached routes on the static RouteHandler.
     */
    public static clearRouteCache() {
        RouteHandler.routeCache = {};
    }

    /** A react-loadable instance. */
    public loadable;

    /** A react node representing the route of the component. */
    public route: React.ReactNode;

    /** Key to identify the route. Components with the same key share the same instance. */
    private key: string;

    public constructor(
        componentPromise: LoadFunction,
        public path: string,
        public url: (data: GeneratorProps) => string,
        loadingComponent: React.ReactNode = Loader,
        key?: string,
    ) {
        this.loadable = Loadable({
            loading: loadingComponent as any,
            loader: componentPromise,
        });
        this.key = key || path;

        const cachedRoute = RouteHandler.routeCache[this.key];
        if (cachedRoute) {
            this.route = cachedRoute;
        } else {
            this.route = <Route exact path={this.path} component={this.loadable} key={this.key} />;
            RouteHandler.routeCache[this.key] = this.route;
        }
    }

    /**
     * A component representing a link to the component.
     *
     * - Preloads the loadable for the component on hover.
     */
    public Link = (props: Omit<NavLinkProps, "to"> & { data: GeneratorProps }) => {
        return (
            <Hoverable duration={50} onHover={this.preload}>
                {provided => <NavLink {...provided} {...props} to={this.url(props.data)} />}
            </Hoverable>
        );
    };

    /**
     * Call this to preload the route.
     */
    public preload = () => {
        return (this.loadable as LoadableComponent).preload();
    };
}
