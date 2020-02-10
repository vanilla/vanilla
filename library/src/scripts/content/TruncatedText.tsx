/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useMeasure } from "@vanilla/react-utils";
import React, { useEffect, useRef } from "react";
import shave from "shave";
import { forceRenderStyles } from "typestyle";

interface IProps {
    tag?: string;
    className?: string;
    children: React.ReactNode;
    useMaxHeight?: boolean;
    lines?: number;
    expand?: boolean;
    maxCharCount?: number;
}

const TruncatedText = React.memo(function TruncatedText(_props: IProps) {
    const ref = useRef<HTMLDivElement | null>(null);
    const props = {
        lines: 3,
        expand: false,
        ..._props,
    };

    const measure = useMeasure(ref);

    useEffect(() => {
        truncate();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [measure]);

    function truncate() {
        forceRenderStyles();
        if (props.useMaxHeight) {
            truncateTextBasedOnMaxHeight();
        } else {
            truncateBasedOnLines();
        }
    }

    /**
     * Truncate element text based on a certain number of lines.
     *
     * @param excerpt - The excerpt to truncate.
     */
    function truncateBasedOnLines() {
        const lineHeight = calculateLineHeight();
        if (lineHeight !== null) {
            const maxHeight = props.lines! * lineHeight;
            shave(ref.current!, maxHeight);
        }
    }

    /**
     * Truncate element text based on max-height
     *
     * @param excerpt - The excerpt to truncate.
     */
    function truncateTextBasedOnMaxHeight() {
        const element = ref.current!;
        const maxHeight = parseInt(getComputedStyle(element)["max-height"], 10);
        if (maxHeight && maxHeight > 0) {
            shave(element, maxHeight);
        }
    }

    function calculateLineHeight(): number | null {
        if (ref.current) {
            return parseInt(getComputedStyle(ref.current)["line-height"], 10);
        } else {
            return null;
        }
    }

    const Tag = (props.tag || "span") as "span";

    let { children } = props;
    if (props.maxCharCount && typeof children === "string") {
        const newChildren = children.slice(0, props.maxCharCount);

        if (newChildren.length < children.length) {
            children += "…";
        }

        children = newChildren;
    }

    return (
        <Tag className={props.className} ref={ref}>
            {props.children}
        </Tag>
    );
});

export default TruncatedText;
