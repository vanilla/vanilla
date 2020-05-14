import React from "react";
import classNames from "classnames";
import { createPortal } from "react-dom";

import { animated } from "react-spring";
import { newPostBackgroundClasses } from "./newPostBackgroundStyles";

interface IProps {
    open: boolean;
    className?: string;
    children: React.ReactNode;
    onClick: (e) => void;
    onKeyDown?: (e) => void;
    bgTransition: any;
}

export default function NewPostBackground(props: IProps) {
    const classes = newPostBackgroundClasses();

    return createPortal(
        <animated.aside
            className={classNames(classes.container)}
            style={props.bgTransition}
            onClick={props.onClick}
            onKeyDown={props.onKeyDown}
        >
            {props.children}
        </animated.aside>,
        document.body,
    );
}
