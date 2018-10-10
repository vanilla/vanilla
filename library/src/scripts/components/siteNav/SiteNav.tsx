/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "../../application";
import classNames from "classnames";
import NavNode from "@library/components/siteNav/NavNode";

interface IProps {
    name: string;
    className?: string;
    children: any[];
}

/**
 * Recursive component to generate site nav
 * No need to set "counter". It will be set automatically. Kept optional to not need to call it on the top level. Used for React's "key" values
 */
export default class SiteNav extends React.Component<IProps> {
    public render() {
        const content =
            this.props.children && this.props.children.length > 0
                ? this.props.children.map((child, i) => {
                      return <NavNode {...child} key={`navNode-${i}`} counter={i} />;
                  })
                : null;
        return (
            <nav
                aria-label={`${t("Category navigation from folder: ")}\"${this.props.name}\"`}
                className={classNames("siteNavigation", this.props.className)}
            >
                {content}
            </nav>
        );
    }
}
