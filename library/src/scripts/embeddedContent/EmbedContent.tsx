/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { FOCUS_CLASS } from "@library/content/embeds/embedUtils";
import classNames from "classnames";
import { useUniqueID } from "@library/utility/idUtils";

interface IProps {
    className?: string;
    type: string;
    description: string;
    children?: React.ReactNode;
}

export function EmbedContent(props: IProps) {
    const id = useUniqueID("richEditor-embed-description-");

    return (
        <div
            aria-describedby={id}
            aria-label={"External embed content - " + props.type}
            className={classNames(FOCUS_CLASS, "embedExternal-content", props.className)}
            tabIndex={-1}
        >
            <span className="sr-only">{props.description}</span>
            {props.children}
        </div>
    );
}
