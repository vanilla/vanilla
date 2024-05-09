/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { countClasses } from "@library/content/countStyles";
import { humanReadableNumber } from "@library/content/NumberFormatted";
import { cx } from "@emotion/css";

export interface IProps {
    className?: string;
    count?: number;
    label: string; // For accessibility, should be in the style of: "Notifications: "
    max?: number;
    useMax?: boolean;
    useFormatted?: boolean;
}

/**
 * Implements Count to display over icon
 */
export default class Count extends React.Component<IProps> {
    public render() {
        const hasCount = !!this.props.count;
        const useMax = this.props.useMax ?? true;
        const useFormatted = this.props.useFormatted ?? false;
        const max = this.props.max || 99;
        const precision = hasCount && this.props.count! > 1050 ? 1 : 0;
        const countValue =
            !!this.props.count && useFormatted ? humanReadableNumber(this.props.count, precision) : this.props.count;
        const maxValue = useFormatted ? `${humanReadableNumber(max, precision)}+` : `${max}+`;
        const maxOrCount = useMax ? maxValue : countValue;
        const visibleCount = hasCount && this.props.count! < max ? countValue : maxOrCount;

        const classes = countClasses();

        return (
            <div className={cx(classes.root, this.props.className)}>
                <span className="sr-only" aria-live="polite">
                    {hasCount ? this.props.label + ` ${this.props.count}` : ""}
                </span>
                {hasCount && (
                    <div className={classes.text} aria-hidden={true}>
                        {visibleCount}
                    </div>
                )}
            </div>
        );
    }
}
