/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { CSSProperties, useMemo } from "react";
import { useTransition, animated } from "react-spring";

interface IProps extends React.HtmlHTMLAttributes<HTMLDivElement> {
    children?: React.ReactNode;
    delay?: number;
    fade?: boolean;
    fromDirection?: FromDirection;
    halfDirection?: boolean;
    isEntered: boolean;
    targetTransform?: Partial<ITargetTransform>;
    asElement?: string;
    onDestroyed?: () => void;
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

export const EntranceAnimation = React.forwardRef<HTMLDivElement, IProps>(function EntranceAnimation(
    _props: IProps,
    ref,
) {
    const {
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

    const transitions = useTransition(isEntered, null, config);
    const AnimatedComponent = asElement ? animated[asElement] : animated.div;

    return transitions.map(({ item, key, props: style }) => {
        return (
            item && (
                <AnimatedComponent {...divProps} ref={ref} key={key} style={style}>
                    {children}
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
