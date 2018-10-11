/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { NavLink } from "react-router-dom";
import { rightTriangle } from "@library/components/Icons";
import Button from "@library/components/forms/Button";
import { t } from "@library/application";
import Paragraph from "@library/components/Paragraph";

interface IProps {
    name: string;
    className?: string;
    children: any[];
    counter: number;
    url: string;
}

export default class NavNode extends React.Component<IProps> {
    public render() {
        const hasChildren = this.props.children && this.props.children.length > 0;
        const childrenContents =
            hasChildren &&
            this.props.children.map((child, i) => {
                return (
                    <NavNode
                        {...child}
                        key={"navNode-" + this.props.counter + "-" + i}
                        counter={this.props.counter! + 1}
                    />
                );
            });

        return (
            <li className={classNames("navNode", this.props.className)}>
                {hasChildren && (
                    <Button>
                        <Paragraph>{t("Toggle Category")}</Paragraph>
                        {rightTriangle()}
                    </Button>
                )}
                <NavLink to={this.props.url} activeClassName="isCurrent">
                    <span className="navNode-label">{this.props.name}</span>
                </NavLink>
                {hasChildren && <ul className="navNode-children">{childrenContents}</ul>}
            </li>
        );
    }
}
