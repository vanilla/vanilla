/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { Link, LinkProps, withRouter, RouteComponentProps } from "react-router-dom";
import { LocationDescriptor } from "history";

interface IProps extends LinkProps, RouteComponentProps<{}> {
    to: string;
}

/**
 * A link that opens the linked item in a modal.
 */
export class ModalLink extends React.Component<IProps> {
    public render() {
        const to: LocationDescriptor = {
            pathname: this.props.to,
            state: {
                modal: true,

                // Pass along the "previous" location so the router can display
                // The old route underneath the modal when clicked.
                lastLocation: this.props.location,
            },
        };
        return <Link to={to} children={this.props.children} />;
    }
}

export default withRouter(ModalLink);
