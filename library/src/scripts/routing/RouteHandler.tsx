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
import SmartLink from "@library/routing/links/SmartLink";
import { formatUrl } from "@library/utility/appUtils";

type LoadFunction = () => Promise<any>;

/**
 * Class for managing routing and matching a particular page.
 */
export default class RouteHandler<GeneratorProps> {
    /** A react-loadable instance. */
    public loadable;

    /** A react node representing the route of the component. */
    public route: React.ReactNode;

    /** Key to identify the route. Components with the same key share the same instance. */
    private key: string;

    public url: (data: GeneratorProps) => string;

    public constructor(
        componentPromise: LoadFunction,
        public path: string | string[],
        url: (data: GeneratorProps) => string,
        loadingComponent: React.ReactNode = Loader,
        key?: string,
    ) {
        this.loadable = Loadable({
            loading: loadingComponent as any,
            loader: componentPromise,
        });
        this.url = (data: GeneratorProps) => formatUrl(url(data), true);
        const finalPath = Array.isArray(path) ? path : [path];
        this.key = key || finalPath.join("-");
        this.route = <Route exact path={path} component={this.loadable} key={this.key} />;
    }

    /**
     * A component representing a link to the component.
     *
     * - Preloads the loadable for the component on hover.
     */
    public Link = (props: Omit<NavLinkProps, "to"> & { data: GeneratorProps }) => {
        return (
            <Hoverable duration={50} onHover={this.preload}>
                {provided => <SmartLink {...provided} {...props} to={this.url(props.data)} />}
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
