/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { useWidgetSectionClasses } from "@library/layout/WidgetLayout.context";
import { mergeRefs } from "@vanilla/react-utils";
import React, { useContext, useEffect } from "react";

export const InterWidgetSpacing = {
    Standard: "standard", // Standard spacing between widgets.
    None: "none", // No spacing
} as const;

export type InterWidgetSpacing = (typeof InterWidgetSpacing)[keyof typeof InterWidgetSpacing];

export interface ILayoutWidgetProps extends React.HTMLAttributes<HTMLDivElement> {
    interWidgetSpacing?: InterWidgetSpacing;
    as?: keyof JSX.IntrinsicElements;
    children?: React.ReactNode;
}

interface IWidgetContext {
    onClick?: React.MouseEventHandler;
    extraClasses?: string;
    extraContent?: React.ReactNode;
    widgetRef: React.MutableRefObject<HTMLElement | null | undefined>;
    widgetRefFn?: (ref: HTMLElement | null) => void;
    tabIndex?: React.HTMLAttributes<any>["tabIndex"];
    childrenWrapperClassName?: string;
    inert?: boolean;
    extraProps?: React.HTMLAttributes<any>;
}

const WidgetContext = React.createContext<IWidgetContext>({
    widgetRef: { current: null },
});

export function useWidgetContext() {
    return useContext(WidgetContext);
}

export function WidgetContextProvider(props: React.PropsWithChildren<IWidgetContext>) {
    const { children, ...value } = props;
    return <WidgetContext.Provider value={value}>{children}</WidgetContext.Provider>;
}

export const LayoutWidget = React.forwardRef(function Widget(
    _props: ILayoutWidgetProps,
    ref: React.MutableRefObject<HTMLElement | null | undefined>,
) {
    const { children, interWidgetSpacing = "standard", ...props } = _props;
    const classes = useWidgetSectionClasses();
    const context = useContext(WidgetContext);

    const HtmlComponent = (props.as ?? "div") as "div"; // Allow the component to be overridden by `as` prop

    return (
        <HtmlComponent
            {...props}
            {...context.extraProps}
            tabIndex={context.tabIndex ?? props.tabIndex}
            onClick={(e) => {
                context.onClick?.(e);
                context.extraProps?.onClick?.(e);
                props.onClick?.(e);
            }}
            ref={mergeRefs(ref, context.widgetRef, context.widgetRefFn)}
            className={cx(
                interWidgetSpacing === "standard" && classes.widgetClass,
                context.extraClasses,
                props.className,
            )}
        >
            <ConditionalWrap
                condition={!!context.childrenWrapperClassName}
                componentProps={{
                    className: context.childrenWrapperClassName,
                    inert: context.inert ? "inert" : undefined,
                }}
            >
                {children}
            </ConditionalWrap>
            {context.extraContent}
        </HtmlComponent>
    );
});
