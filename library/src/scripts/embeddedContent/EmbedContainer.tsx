/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { DetailedHTMLProps } from "react";
import { embedContainerClasses } from "@library/embeddedContent/embedStyles";
import classNames from "classnames";

export enum EmbedContainerSize {
    SMALL = "small",
    MEDIUM = "medium",
    FULL_WIDTH = "fullwidth",
}

interface IProps extends DetailedHTMLProps<React.HTMLAttributes<HTMLDivElement>, HTMLDivElement> {
    className?: string;
    children?: React.ReactNode;
    size?: EmbedContainerSize;
    inEditor?: boolean;
    withPadding?: boolean;
}

export function EmbedContainer(props: IProps) {
    const { size, inEditor, ...htmlProps } = props;
    const classes = embedContainerClasses();

    return (
        <div
            {...htmlProps}
            className={classNames(
                classes.makeRootClass(props.size || EmbedContainerSize.MEDIUM, !!props.inEditor, !!props.withPadding),
                props.className,
            )}
        >
            {props.children}
        </div>
    );
}
