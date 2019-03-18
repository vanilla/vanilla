/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { RouteComponentProps, Switch, withRouter } from "react-router";
import { Action, Location } from "history";

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

    public componentDidMount() {
        this.props.history.listen(this.onHistoryUpdate);
    }

    private onHistoryUpdate = (location: Location, action: Action) => {
        if (action === "PUSH") {
            window.scrollTo(0, 0);
        }
    };

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
