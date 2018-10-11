/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import { newFolder } from "@library/components/Icons";

export interface IFrameFooterProps {
    className?: string;
    children: JSX.Element | JSX.Element[];
}

/**
 * Generic footer for frame component
 */
export default class FrameFooter extends React.PureComponent<IFrameFooterProps> {
    public static defaultProps = {
        validSelection: false,
    };

    public render() {
        return <footer className={classNames("frameFooter", this.props.className)}>{this.props.children}</footer>;
    }
}
