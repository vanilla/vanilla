/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { NavLink } from "react-router-dom";
import { downTriangle, rightTriangle } from "@library/components/Icons";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { t } from "@library/application";

interface IProps {
    name: string;
    className?: string;
    titleID?: string;
    children: any[];
    counter: number;
    url: string;
}

interface IState {
    open: boolean;
}

export default class SiteNavNode extends React.Component<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            open: props.openRecursive,
        };
        this.open = this.open.bind(this);
        this.close = this.close.bind(this);
        this.toggle = this.toggle.bind(this);
    }

    public open() {
        this.setState({
            open: true,
        });
    }

    public close() {
        this.setState({
            open: true,
        });
    }

    public toggle() {
        this.setState({
            open: !this.state.open,
        });
    }

    public render() {
        const hasChildren = this.props.children && this.props.children.length > 0;
        const topLevel = this.props.counter === 1;
        const childrenContents =
            hasChildren &&
            this.props.children.map((child, i) => {
                return (
                    <SiteNavNode
                        {...child}
                        key={"siteNavNode-" + this.props.counter + "-" + i}
                        counter={this.props.counter! + 1}
                    />
                );
            });
        return (
            <li
                role="treeitem"
                className={classNames("siteNavNode", this.props.className)}
                aria-expanded={this.state.open}
            >
                {hasChildren && (
                    <Button
                        tabIndex={-1}
                        ariaHidden={true}
                        title={t("Toggle Category")}
                        ariaLabel={t("Toggle Category")}
                        onClick={this.toggle}
                        baseClass={ButtonBaseClass.CUSTOM}
                        className="siteNavNode-toggle"
                    >
                        {this.state.open ? downTriangle(t("Expand")) : rightTriangle(t("Collapse"))}
                    </Button>
                )}
                <NavLink
                    className={classNames("siteNavNode-link", { hasChildren })}
                    tabIndex={0}
                    to={this.props.url}
                    activeClassName="isCurrent"
                >
                    {!hasChildren && <span className="siteNavNode-spacer" aria-hidden={true} />}
                    <span className="siteNavNode-label">{this.props.name}</span>
                </NavLink>
                {hasChildren &&
                    this.state.open && (
                        <ul className="siteNavNode-children" role="group">
                            {childrenContents}
                        </ul>
                    )}
            </li>
        );
    }
}
