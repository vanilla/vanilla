/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";

export interface ICompactSearchProps {
    className?: string;
    onClick: () => void;
    open: boolean;
}
interface IState {}

/**
 * Implements Compact Search component for header
 */
export default class CompactSearch extends React.Component<ICompactSearchProps> {
    public render() {
        return <div className={classNames(this.props.className)} />;
    }
}
