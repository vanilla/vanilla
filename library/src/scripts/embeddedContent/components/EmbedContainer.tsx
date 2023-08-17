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
    withShadow?: boolean;
}

export const EmbedContainer = forwardRef(function EmbedContainer(props: IProps, ref: React.Ref<HTMLDivElement>) {
    const { size, withPadding, withShadow = true, ...htmlProps } = props;
    const classes = embedContainerClasses();

    const { inEditor, selectSelf, isSelected } = useEmbedContext();
    return (
        <div
            ref={ref}
            {...htmlProps}
            onClick={(e) => {
                htmlProps.onClick?.(e);
                if (inEditor) {
                    e.stopPropagation();
                    e.nativeEvent.stopImmediatePropagation();
                    if (!isSelected) {
                        e.preventDefault();
                        selectSelf?.();
                    }
                }
            }}
            className={classNames(
                "embedExternal",
                classes.makeRootClass(props.size || EmbedContainerSize.MEDIUM, !!inEditor, !!withPadding, !!withShadow),
                props.className,
            )}
        >
            {props.children}
        </div>
    );
});
