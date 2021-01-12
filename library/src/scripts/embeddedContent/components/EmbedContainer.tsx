/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useEmbedContext } from "@library/embeddedContent/IEmbedContext";
import { embedContainerClasses } from "@library/embeddedContent/components/embedStyles";
import classNames from "classnames";
import React, { DetailedHTMLProps, forwardRef } from "react";
import { EmbedContainerSize } from "./EmbedContainerSize";

interface IProps extends DetailedHTMLProps<React.HTMLAttributes<HTMLDivElement>, HTMLDivElement> {
    className?: string;
    children?: React.ReactNode;
    size?: EmbedContainerSize;
    withPadding?: boolean;
}

export const EmbedContainer = forwardRef(function EmbedContainer(props: IProps, ref: React.Ref<HTMLDivElement>) {
    const { size, withPadding, ...htmlProps } = props;
    const classes = embedContainerClasses();

    const { inEditor } = useEmbedContext();
    return (
        <div
            ref={ref}
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
});
