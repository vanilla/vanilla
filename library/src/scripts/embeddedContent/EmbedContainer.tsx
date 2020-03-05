/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useEmbedContext } from "@library/embeddedContent/embedService";
import { embedContainerClasses } from "@library/embeddedContent/embedStyles";
import classNames from "classnames";
import React, { DetailedHTMLProps } from "react";

export enum EmbedContainerSize {
    SMALL = "small",
    MEDIUM = "medium",
    FULL_WIDTH = "fullwidth",
}

interface IProps extends DetailedHTMLProps<React.HTMLAttributes<HTMLDivElement>, HTMLDivElement> {
    className?: string;
    children?: React.ReactNode;
    size?: EmbedContainerSize;
    withPadding?: boolean;
}

export function EmbedContainer(props: IProps) {
    const { size, withPadding, ...htmlProps } = props;
    const classes = embedContainerClasses();

    const { inEditor } = useEmbedContext();
    return (
        <div
            {...htmlProps}
            className={classNames(
                "embedExternal",
                classes.makeRootClass(props.size || EmbedContainerSize.MEDIUM, !!inEditor, !!withPadding),
                props.className,
            )}
        >
            {props.children}
        </div>
    );
}
