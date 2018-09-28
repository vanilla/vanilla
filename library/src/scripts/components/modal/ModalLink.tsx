/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { NavLinkProps, NavLink, withRouter } from "react-router-dom";
import { LocationDescriptor } from "history";

interface IProps extends NavLinkProps {
    to: string;
}

/**
 * A link that opens the linked item in a modal.
 */
export class ModalLink extends React.Component<IProps> {
    public render() {
        const { to, ...rest } = this.props;
        const location: LocationDescriptor = {
            pathname: to,
            state: {
                modal: true,

                // Pass along the "previous" location so the router can display
                // The old route underneath the modal when clicked.
                lastLocation: this.props.location,
            },
        };
        return <NavLink to={location} {...rest} />;
    }
}

export default withRouter(ModalLink as any);
