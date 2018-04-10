import React from 'react';
import { getRoutes, getMeta } from "@core/application";
import { BrowserRouter as Router, Route, Switch } from "react-router-dom";
import NotFoundPage from "@core/Main/NotFoundPage";

/**
 * The root application component.
 *
 * This component renders the top-level pages that have been registered with {@link module:application.addRoutes}.
 */
export default class App extends React.PureComponent {
    render() {
        const routes = getRoutes().map((route) => {
            return <route.type key={route.key || route.props.path + (route.props.exact ? '!' : '')} {...route.props} />;
        });

        routes.push(<Route key="@not-found" component={NotFoundPage} />);

        return <Router basename={getMeta('context.basePath', '')}>
            <Switch>{routes}</Switch>
        </Router>;
    }
}
