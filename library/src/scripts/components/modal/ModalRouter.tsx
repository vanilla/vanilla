/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { Switch, RouteComponentProps, withRouter } from "react-router-dom";

interface IProps extends RouteComponentProps<{}> {
    modalRoutes: React.ReactNode[];
    pageRoutes: React.ReactNode[];
}

/**
 * Routing component for pages and modals in the /kb directory.
 */
class ModalRouter extends React.Component<IProps> {
    public render() {
        const { location, modalRoutes, pageRoutes } = this.props;

        return (
            <React.Fragment>
                <Switch location={this.isModal ? this.lastLocation : location}>{pageRoutes}</Switch>
                <Switch>{this.isModal ? modalRoutes : null}</Switch>
            </React.Fragment>
        );
    }

    private get lastLocation() {
        return this.props.location.state.lastLocation;
    }

    /**
     * Whether or not the we are navigated inside of a router.
     */
    private get isModal(): boolean {
        const { location } = this.props;
        return !!(location && location.state && location.state.modal);
    }
}

export default withRouter(ModalRouter);
