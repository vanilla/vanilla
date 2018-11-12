/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { Component } from "react";
import isExternal from "is-url-external";
import { Link } from "react-router-dom";

interface IProps {
    to: string;
    children: React.ReactNode;
    className?: string;
}

/**
 * Renders internal or external link
 */
export default class InternalOrExternalLink extends Component<IProps> {
    public render() {
        if (isExternal(this.props.to)) {
            return (
                <a {...this.props} href={this.props.to} className={this.props.className}>
                    {this.props.children}
                </a>
            );
        } else {
            return (
                <Link {...this.props} to={this.props.to} className={this.props.className}>
                    {this.props.children}
                </Link>
            );
        }
    }
}
