/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import Loadable, { LoadableComponent } from "react-loadable";
import Loader from "../loaders/Loader";
import { Route, NavLinkProps, NavLink } from "react-router-dom";
import { Omit } from "react-redux";
import Hoverable from "../utility/Hoverable";

type LoadFunction = () => Promise<any>;

/**
 * Class for managing routing and matching a particular page.
 */
export default class RouteHandler<GeneratorProps> {
    public loadable;
    public route: React.ReactNode;
    private key: string;

    public constructor(
        componentPromise: LoadFunction,
        public path: string,
        public url: (data: GeneratorProps) => string,
        loadingComponent: React.ReactNode = Loader,
        key?: string,
    ) {
        this.loadable = Loadable({
            loading: loadingComponent,
            loader: componentPromise,
        });
        this.key = key || path;
        this.route = <Route exact path={this.path} component={this.loadable} key={this.key} />;
    }

    public Link = (props: Omit<NavLinkProps, "to"> & { data: GeneratorProps }) => {
        return (
            <Hoverable duration={50} onHover={this.preload}>
                {provided => <NavLink {...provided} {...props} to={this.url(props.data)} />}
            </Hoverable>
        );
    };

    public preload = () => {
        (this.loadable as LoadableComponent).preload();
    };
}
