/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { CSSProperties } from "react";
import { useTransition, animated } from "react-spring";

export enum ModalTransitionType {
    FADE_IN = "fadein",
    SLIDE_LEFT = "slideleft",
    SLIDE_RIGHT = "slideRight",
}

export interface IModalTransitioner {
    isVisible: boolean;
    transitionType: ModalTransitionType;
}

interface IProps extends IModalTransitioner {
    children: React.ReactNode;
}

interface ITransition {
    from: CSSProperties;
    enter: CSSProperties;
    leave: CSSProperties;
}

const TRANSITION_FADE_IN: ITransition = {
    from: { opacity: 0 },
    enter: { opacity: 1 },
    leave: { opacity: 0 },
};

const TRANSITION_SLIDE_RIGHT: ITransition = {
    from: {
        position: "relative",
        transform: "translate3d(100%, 0, 0)",
    },
    enter: {
        transform: "translate3d(0, 0, 0)",
    },
    leave: {
        transform: "translate3d(100%, 0, 0)",
    },
};

export function ModalTransition(props: IProps) {
    let transition: ITransition = TRANSITION_FADE_IN;
    switch (props.transitionType) {
        case ModalTransitionType.SLIDE_RIGHT:
            transition = TRANSITION_SLIDE_RIGHT;
    }

    const transitions = useTransition(props.isVisible, null, transition);

    return transitions.map(({ item, key, props: style }) => {
        return (
            item && (
                <animated.div key={key} style={style}>
                    {props.children}
                </animated.div>
            )
        );
    });
}
