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
 * Format a compact value of a number.
 *
 * @param props
 */
export function formatNumberText(props: { value: number }) {
    const { value } = props;
    numeral.localeData("en");
    const initialValue = numeral(value);
    const compactValue = stripTrailingZeros(initialValue.format("0a.0"));
    const fullValue = initialValue.format();
    const isAbbreviated = fullValue.toString() !== initialValue.value().toString();

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
