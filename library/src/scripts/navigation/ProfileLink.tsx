/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { makeProfileUrl } from "../utility/appUtils";
import classNames from "classnames";

interface IProps {
    username: string;
    className?: string;
    children?: React.ReactNode;
}

/**
 * Class representing a link to a users profile. This will do a full page refresh.
 */
export default class ProfileLink extends React.Component<IProps> {
    public render() {
        const { username } = this.props;
        const children = this.props.children || username;
        return (
            <a href={makeProfileUrl(username)} className={classNames(this.props.className)}>
                {children}
            </a>
        );
    }
}
