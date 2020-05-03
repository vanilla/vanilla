import React from "react";
import classNames from "classnames";
import { createPortal } from "react-dom";

import { useSpring, animated } from "react-spring";
import { newPostBackgroundClasses, newPostBackgroundVariables } from "./newPostBackgroundStyles";
import { colorOut } from "@library/styles/styleHelpers";

interface IProps {
    open: boolean;
    className?: string;
    children: React.ReactNode;
    onClick: (e) => void;
}

export default function NewPostBackground(props: IProps) {
    const classes = newPostBackgroundClasses();
    const vars = newPostBackgroundVariables();

    const trans = useSpring({
        backgroundColor: props.open ? colorOut(vars.container.color.open) : colorOut(vars.container.color.close),
        from: { backgroundColor: colorOut(vars.container.color.close) },
        config: { duration: vars.container.duration },
    });

    return createPortal(
        <animated.aside className={classNames(classes.container)} style={trans} onClick={props.onClick}>
            {props.children}
        </animated.aside>,
        document.body,
    );
}
