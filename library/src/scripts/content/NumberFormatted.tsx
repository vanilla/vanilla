/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { numberFormattedClasses } from "@library/content/NumberFormatted.styles";

interface IProps {
    value: number;
    className?: string;
    title?: string;
    fallbackTag?: string;
}
/**
 * Strip trailing 0s from a string.
 *
 * @param value
 */
function stripTrailingZeros(value: string): string {
    if (isNaN(Number(value))) {
        return value;
    } else {
        return Number(value).toString();
    }
}

/**
 * Convert number to human readable value.
 *
 * @param value
 * @param precision
 * @returns string
 */
export function humanReadableNumber(value: number, precision: number = 1): string {
    const valueAbs = Math.abs(value);
    const negativeValue = value < 0 ? "-" : "";
    if (valueAbs < 1e3) return valueAbs.toFixed(precision);
    if (valueAbs >= 1e3 && valueAbs < 1e6) return negativeValue + (valueAbs / 1e3).toFixed(precision) + "k";
    if (valueAbs >= 1e6 && valueAbs < 1e9) return negativeValue + (valueAbs / 1e6).toFixed(precision) + "m";
    if (valueAbs >= 1e9 && valueAbs < 1e12) return negativeValue + (valueAbs / 1e9).toFixed(precision) + "b";
    return negativeValue + (valueAbs / 1e12).toFixed(precision) + "t";
}
/**
 * Convert number to string with commas
 *
 * @param value
 * @param precision
 * @returns
 */
export function numberWithCommas(value: number, precision: number = 0): string {
    return value.toFixed(precision).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Format a compact value of a number.
 *
 * @param props
 */
export function formatNumberText(props: { value: number }) {
    const { value } = props;
    const compactValue = stripTrailingZeros(humanReadableNumber(value));
    const fullValue = numberWithCommas(value);
    const isAbbreviated = fullValue !== value.toString();

    return {
        compactValue,
        fullValue,
        isAbbreviated,
    };
}

/**
 * If you're building a translated stirng, it can be helpful to get the same data as "NumberFormatted" but decomposed
 */
export function decomposedNumberFormatted(props: IProps) {
    const formattedNumber = formatNumberText({ value: props.value });
    const { fullValue, isAbbreviated } = formattedNumber;
    const Tag = (isAbbreviated ? `abbr` : props.fallbackTag ?? `span`) as "span";
    const className = classNames("number", props.className, numberFormattedClasses().root);
    const title = props.title || fullValue;

    return {
        ...formattedNumber,
        Tag,
        attributes: {
            className,
            title,
        },
    };
}

/**
 * A component to format numbers to plug into <Translate/>. The <NumberFormatted/> below does not play well with the component <Translate/>
 */
export function numberFormattedForTranslations(props: IProps) {
    const { Tag, attributes, compactValue } = decomposedNumberFormatted(props);
    return <Tag {...attributes}>{compactValue}</Tag>;
}

/**
 * A component to format numbers. The react number format supports localization. The way we'll pass in the options are TBD.
 */
export default function NumberFormatted(props: IProps) {
    const { compactValue, Tag, attributes } = decomposedNumberFormatted(props);
    return <Tag {...attributes}>{compactValue}</Tag>;
}
