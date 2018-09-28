/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import { newFolder } from "@library/components/Icons";

interface IProps {
    className?: string;
    children: JSX.Element;
}

/**
 * Generic footer for frame component
 */
export default class FrameFooter extends React.PureComponent<IProps> {

    public defaultProps = {
        validSelection: false,
    };

    public render() {
        return (
            <footer className={classNames('flyoutFooter', this.props.className)}>
                {this.props.children}
            </footer>
        );
    }
}


