/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import numeral from "numeral";
import { numberFormattedClasses } from "@library/content/NumberFormatted.styles";

interface IProps {
    value: number;
    className?: string;
}

/**
 * A component to format numbers. The react number format supports localization. The way we'll pass in the options are TBD.
 */
export default class NumberFormatted extends React.Component<IProps> {
    public render() {
        numeral.localeData("en"); //
        const { className } = this.props;
        const value = numeral(this.props.value);
        const compactValue = this.stripTrailingZeros(value.format("0a.0"));
        const fullValue = value.format();
        const classes = numberFormattedClasses();

        const Tag = fullValue === compactValue ? `span` : `abbr`;
        return (
            <Tag title={fullValue} className={classNames("number", className, classes.root)}>
                {compactValue}
            </Tag>
        );
    }

    private stripTrailingZeros(value: string) {
        if (isNaN(Number(value))) {
            return value;
        } else {
            return Number(value).toString();
        }
    }
}
