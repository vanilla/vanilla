/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, cx } from "@emotion/css";
import { useMemo } from "react";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
    wrap?: boolean;
    gap?: number | string;
    justify?: "start" | "center" | "end";
    align?: "start" | "center" | "end";
    width?: string | number;
}

export function Row(props: IProps) {
    const { children, wrap, gap, justify, align, width, ...rest } = props;
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

    return (
        <span {...rest} className={cx(classes, props.className)}>
            {children}
        </span>
    );
}
