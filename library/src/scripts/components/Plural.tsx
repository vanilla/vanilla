/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import Translate from "@library/components/translation/Translate";

interface IProps {
    value: number;
    singular?: string;
    plural: string;
    className?: string;
}

/**
 * Implements helper plural components for displaying singular or plural translation strings based on the count and locale.
 */
export default class Plural extends React.Component<IProps> {
    public render() {
        const { value, singular, className } = this.props;
        const plural = this.props.plural ? this.props.plural : `${singular}s`;
        const title = `${<Translate source={value === 1 ? singular : plural} c0={value} />}`;
        return (
            <span title={title} className={classNames("number", className)}>
                {this.props.value}
            </span>
        );
    }
}
