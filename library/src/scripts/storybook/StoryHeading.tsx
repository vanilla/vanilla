/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { CSSProperties } from "react";
import { StorySeparator } from "@library/storybook/StorySeparator";

export interface IStoryHeadingProps {
    depth?: 1 | 2 | 3 | 4 | 5 | 6;
    children: React.ReactNode;
}

/**
 * Heading component, for react storybook.
 */
export function StoryHeading(props: IStoryHeadingProps) {
    const depth = props.depth ? props.depth : 2;
    const Tag = `h${depth}` as "h1" | "h2" | "h3" | "h4" | "h5" | "h6";
    return (
        <>
            {Tag !== "h1" && <StorySeparator />}
            <Tag
                style={
                    {
                        opacity: (7 - depth) * 0.15 + (depth - 1) * 0.1,
                        fontSize: `${30 - (depth - 1) * 2}px`,
                        textAlign: "center",
                        display: "block",
                        textTransform: depth !== 1 ? "uppercase" : undefined,
                        fontStyle: depth !== 1 ? "italic" : undefined,
                    } as CSSProperties
                }
            >
                {props.children}
            </Tag>
            {Tag === "h2" && <StorySeparator width="500px" />}
        </>
    );
}
