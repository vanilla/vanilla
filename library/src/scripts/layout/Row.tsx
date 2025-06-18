/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, cx } from "@emotion/css";
import { useMemo } from "react";

interface IRowProps extends React.HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
    tag?: string;
    wrap?: boolean;
    gap?: number | string;
    justify?: "start" | "center" | "end" | "space-between" | "space-around" | "flex-end";
    align?: "start" | "center" | "end" | "baseline";
    width?: string | number;
}

export function Row(props: IRowProps) {
    const { children, tag, wrap, gap, justify, align, width, ...rest } = props;
    const classes = useMemo(() => {
        return css({
            display: "flex",
            flexDirection: "row",
            flexWrap: wrap ? "wrap" : "nowrap",
            gap,
            justifyContent: justify,
            alignItems: align,
            width,
        });
    }, [wrap, gap, justify, align, width]);

    const Tag = (tag || "span") as "span";

    return (
        <Tag {...rest} className={cx(classes, props.className)}>
            {children}
        </Tag>
    );
}
