/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { animated, useTransition } from "react-spring";
import classNames from "classnames";

interface IProps extends React.HtmlHTMLAttributes<HTMLDivElement> {
    // Whether or not the element has entered the screen and should be visible.
    // NOTE: For animations OUT to work, you need to continue to render the element throughout it's transition.
    // This means you can't do {isVisible && <EntranceAnimation />}
    isEntered: boolean;

    // The element to render as. All HTML props are supported.
    // If using a fixed position element, make you apply all position to THIS element.
    // Eg. <EntranceAnimation asElement="header" /> === <header {...transitionProps} {...otherProps} />
    asElement?: string;

    // The contents that are being transitioned.
    children?: React.ReactNode;

    // A delay before the transition starts.
    delay?: number;

    // Whether or not the element should fade in.
    fade?: boolean;

    // The direction to transition in from (if applicable).
    fromDirection?: FromDirection;

    // If set the element will transition from the fromDirection, but it will start halfway away.
    halfDirection?: boolean;

    // For elements that are already transformed through CSS
    // The transition may apply it's own transform that conflicts.
    // Give the target transform percentages here so that calculations can be correct.
    targetTransform?: Partial<ITargetTransform>;

    // Callback that is called when the animated element is removed from the DOM (transition out complete).
    onDestroyed?: () => void;

    // Special class to apply when the first child has multiple
    firstItemProps?: React.HtmlHTMLAttributes<HTMLDivElement>;
    lastItemProps?: React.HtmlHTMLAttributes<HTMLDivElement>;
}

export enum FromDirection {
    TOP = "top",
    LEFT = "left",
    RIGHT = "right",
    BOTTOM = "bottom",
}

export interface ITargetTransform {
    xPercent?: number;
    yPercent?: number;
}

/**
 * A component for transitioning some child entering in.
 *
 * It's transition is configurable through props.
 */
export const EntranceAnimation = React.forwardRef<HTMLDivElement, IProps>(function EntranceAnimation(
    _props: IProps,
    ref,
) {
    const {
        firstItemProps,
        lastItemProps,
        isEntered,
        delay,
        fromDirection,
        fade,
        asElement,
        halfDirection,
        targetTransform,
        children,
        onDestroyed,
        ...divProps
    } = _props;
    const config = useTransitionConfig(_props);

    const transitions = useTransition(isEntered ? children : null, item => item?.key, config);
    const AnimatedComponent = asElement ? animated[asElement] : animated.div;

    return transitions.map(({ item, key, props: style }, i) => {
        const isFirst = i === 0;
        const isLast = i === transitions.length - 1;
        const classes = classNames(
            divProps.className,
            isFirst && firstItemProps?.className,
            isLast && lastItemProps?.className,
        );

        return (
            item && (
                <AnimatedComponent
                    {...divProps}
                    {...(isFirst ? firstItemProps ?? {} : {})}
                    {...(isLast ? lastItemProps ?? {} : {})}
                    className={classes}
                    ref={ref}
                    key={key}
                    style={style}
                >
                    {item}
                </AnimatedComponent>
            )
        );
    });
});

const useTransitionConfig = (props: IProps) => {
    const { fromDirection, delay, fade, halfDirection, targetTransform, onDestroyed } = props;
    const { xPercent = 0, yPercent = 0 } = targetTransform ?? {};

    return useMemo(() => {
        // Opacity
        const startOpacity = fade ? 0 : 1;
        const endOpacity = 1;

        let startTransform: string | undefined = undefined;
        let endTransform: string | undefined = undefined;

        if (fromDirection) {
            // Sliding
            let startX = 0;
            let endX = 0;
            let startY = 0;
            let endY = 0;
            const offsetPercent = halfDirection ? 25 : 100;

            switch (fromDirection) {
                case FromDirection.BOTTOM:
                    startX = xPercent;
                    endX = xPercent;
                    startY = yPercent + offsetPercent;
                    endY = yPercent;
                    break;
                case FromDirection.TOP:
                    startX = xPercent;
                    endX = xPercent;
                    startY = yPercent - offsetPercent;
                    endY = yPercent;
                    break;
                case FromDirection.RIGHT:
                    startX = xPercent + offsetPercent;
                    endX = xPercent;
                    startY = yPercent;
                    endY = yPercent;
                    break;
                case FromDirection.LEFT:
                    startX = xPercent - offsetPercent;
                    endX = xPercent;
                    startY = yPercent;
                    endY = yPercent;
                    break;
            }

            startTransform = `translate3d(${startX}%, ${startY}%, 0)`;
            endTransform = `translate3d(${endX}%, ${endY}%, 0)`;
        }

        const result = {
            config: {
                tension: 250,
                friction: 30,
                clamp: true,
            },
            trail: delay || 0,
            onDestroyed: () => {
                onDestroyed?.();
            },
            from: {
                pointerEvents: "none",
                opacity: startOpacity,
                transform: startTransform,
            },
            enter: {
                opacity: endOpacity,
                pointerEvents: "initial",
                transform: endTransform,
            },
            leave: {
                opacity: startOpacity,
                pointerEvents: "none",
                transform: startTransform,
            },
        };

        // Cleanup undefined values that useTransition can't handle.
        if (!result.from.transform) {
            delete result.from.transform;
        }
        if (!result.enter.transform) {
            delete result.enter.transform;
        }
        if (!result.leave.transform) {
            delete result.leave.transform;
        }
        return result;
    }, [fade, fromDirection, delay, halfDirection, xPercent, yPercent, onDestroyed]);
};
