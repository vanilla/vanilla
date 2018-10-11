/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { NavLink } from "react-router-dom";
import { rightTriangle, downTriangle } from "@library/components/Icons";
import Button from "@library/components/forms/Button";
import { t } from "@library/application";
import Paragraph from "@library/components/Paragraph";

interface IProps {
    name: string;
    className?: string;
    titleID?: string;
    children: any[];
    counter: number;
    url: string;
    openRecursive?: boolean;
}

interface IState {
    open: boolean;
}

export default class NavNode extends React.Component<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            open: props.openRecursive,
        };
        this.open = this.open.bind(this);
        this.close = this.close.bind(this);
        this.toggle = this.toggle.bind(this);
    }

    public open(recursive?) {
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
                    <NavNode
                        {...child}
                        key={"navNode-" + this.props.counter + "-" + i}
                        counter={this.props.counter! + 1}
                        openRecursive={this.props.openRecursive}
                    />
                );
            });

        return (
            <li
                role={topLevel ? "tree" : "group"}
                className={classNames("navNode", this.props.className)}
                aria-labelledby={this.props.titleID}
                aria-expanded={this.state.open}
            >
                {hasChildren && (
                    <Button
                        tabIndex={-1}
                        ariaHidden={true}
                        title={t("Toggle Category")}
                        ariaLabel={t("Toggle Category")}
                        onClick={this.toggle}
                    >
                        {this.state.open ? downTriangle() : rightTriangle()}
                    </Button>
                )}
                <NavLink tabIndex={0} to={this.props.url} activeClassName="isCurrent">
                    <span className="navNode-label">{this.props.name}</span>
                </NavLink>
                {hasChildren &&
                    this.state.open && (
                        <ul className="navNode-children" role="group">
                            {childrenContents}
                        </ul>
                    )}
            </li>
        );
    }
}
