/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { formatUrl } from "@library/application";
import { NavLinkProps, NavLink } from "react-router-dom";
import { LocationDescriptor } from "history";

export const LinkContext = React.createContext(formatUrl("/", true));

interface IProps extends NavLinkProps {}

/**
 * Link component that checks it's <LinkContext /> to know if it needs to do a full refresh
 * or a partial refresh of the page.
 *
 * If the passed `to` is a subset of the context then a partial navigation will be completed.
 *
 * Eg.
 * Context = https://test.com/root
 * To = https://test.com/root/someUrl/deeper/nested
 * Result = /root/someUrl/deeper/nested (react router navigation)
 *
 * Context = https://test.com/otherRoot
 * To = https://test.com/root/someUrl/deeper/nested
 * Result = https://test.com/root/someUrl/deeper/nested (full refresh)
 */
export default function SmartLink(props: IProps) {
    return (
        <LinkContext.Consumer>
            {contextRoot => {
                const href = formatUrl(props.to.toString(), true);

                if (href.startsWith(contextRoot)) {
                    let newTo: LocationDescriptor;
                    const newPath = props.to.toString().replace(window.location.origin, "");

                    if (typeof props.to === "string") {
                        newTo = {
                            pathname: newPath,
                        };
                    } else {
                        newTo = {
                            ...props.to,
                            pathname: newPath,
                        };
                    }

                    return <NavLink {...props} to={newTo} />;
                } else {
                    return <a {...props} href={props.to.toString()} />;
                }
            }}
        </LinkContext.Consumer>
    );
}
