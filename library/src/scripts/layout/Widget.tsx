/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { useWidgetSectionClasses } from "@library/layout/WidgetLayout.context";
import React, { useContext } from "react";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    withContainer?: boolean;
    children?: React.ReactNode;
}

interface IWidgetContext {
    onClick?: React.MouseEventHandler;
    extraClasses?: string;
    extraContent?: React.ReactNode;
    widgetRef?: React.MutableRefObject<HTMLElement | null | undefined>;
    tabIndex?: React.HTMLAttributes<any>["tabIndex"];
}

const WidgetContext = React.createContext<IWidgetContext>({});

export const Widget = React.forwardRef(function Widget(
    _props: IProps,
    ref: React.MutableRefObject<HTMLElement | null | undefined>,
) {
    const { withContainer, children, ...props } = _props;
    const classes = useWidgetSectionClasses();
    const context = useContext(WidgetContext);
    return (
        <div
            {...props}
            tabIndex={context.tabIndex ?? props.tabIndex}
            onClick={context.onClick}
            ref={(refInstance) => {
                if (ref) {
                    ref.current = refInstance;
                }

                if (context.widgetRef) {
                    context.widgetRef.current = refInstance;
                }
            }}
            className={cx(
                withContainer ? classes.widgetWithContainerClass : classes.widgetClass,
                context.extraClasses,
                props.className,
            )}
        >
            {children}
            {context.extraContent}
        </div>
    );
});

export function WidgetContextProvider(props: React.PropsWithChildren<IWidgetContext>) {
    const { children, ...value } = props;
    return <WidgetContext.Provider value={value}>{children}</WidgetContext.Provider>;
}
