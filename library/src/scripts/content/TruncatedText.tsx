/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import { truncatedTextClasses } from "@library/content/TruncatedText.styles";
import { useMeasure } from "@vanilla/react-utils";
import React, { useEffect, useRef, useState } from "react";

interface IProps {
    tag?: string;
    className?: string;
    children: React.ReactNode;
    useMaxHeight?: boolean;
    lines?: number;
    maxCharCount?: number;
}

const TruncatedText = React.memo(function TruncatedText(_props: IProps) {
    const ref = useRef<HTMLDivElement | null>(null);
    const props = {
        lines: 3,
        expand: false,
        ..._props,
    };

    // This state is used to track the number of lines to display.
    // Its values should match https://www.w3.org/TR/css-overflow-3/#propdef--webkit-line-clamp
    const [linesToClamp, setLinesToClamp] = useState<number | "none">(props.lines || "none");
    const classes = truncatedTextClasses({ lineClamp: linesToClamp });

    const measure = useMeasure(ref);

    useEffect(() => {
        if (props.useMaxHeight) {
            setLinesToClamp(() => calculateLinesToClamp(ref.current));
        }
    }, [measure, props.useMaxHeight]);

    /**
     * This function calculates the number of lines to display by dividing the maximum defined height
     * by the line-height and returning the closest integer that will fit into that space.
     */
    const calculateLinesToClamp = (element: HTMLDivElement | null): number | "none" => {
        if (ref.current) {
            const lineHeight = parseInt(getComputedStyle(ref.current)["line-height"], 10);
            const maxHeight = parseInt(getComputedStyle(ref.current)["max-height"], 10);
            if (!Object.is(lineHeight, NaN) && !Object.is(maxHeight, NaN)) {
                return parseInt((maxHeight / lineHeight).toFixed(0)) - 1;
            }
        }
        return "none";
    };

    const Tag = (props.tag || "span") as "span";

    let { children } = props;
    if (props.maxCharCount && typeof children === "string") {
        let newChildren = children.slice(0, props.maxCharCount);

        if (newChildren.length < children.length) {
            newChildren += "…";
        }

        children = newChildren;
    }

    return (
        <Tag className={cx(classes.truncated, props.className)} ref={ref}>
            {children}
        </Tag>
    );
});

export default TruncatedText;
