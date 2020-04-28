import React from "react";
import classNames from "classnames";
import { createPortal } from "react-dom";

import { useSpring, animated } from "react-spring";
import { newPostBackgroundClasses } from "./newPostBackgroundStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut } from "@library/styles/styleHelpers";

export default function NewPostBackground(props) {
    const classes = newPostBackgroundClasses();
    const globalVars = globalVariables();
    const { elementaryColors } = globalVars;

    const c = useSpring({
        backgroundColor: props.open ? colorOut(elementaryColors.black.fade(0.4)) : colorOut(globalVars.mainColors.bg),
        from: { backgroundColor: colorOut(globalVars.mainColors.bg) },
        config: { duration: 300 },
    });

    return createPortal(
        <animated.div className={classNames(classes.container)} style={c} onClick={props.onClick}>
            {props.children}
        </animated.div>,
        document.body,
    );
}
