/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useRef, useLayoutEffect, useMemo } from "react";
import { collapsableContentClasses } from "@library/content/collapsableContentStyles";
import { DownTriangleIcon, ChevronUpIcon } from "@library/icons/common";
import classNames from "classnames";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { useMeasure, useLastValue } from "@vanilla/react-utils";
import { useSpring } from "react-spring";
import { animated } from "react-spring";

interface IProps {
    children: React.ReactNode;
    maxHeight: number;
    className?: string;
    isExpandedDefault?: boolean;
}

export function CollapsableContent(props: IProps) {
    const { isExpandedDefault } = props;
    const [isExpanded, setIsExpanded] = useState(isExpandedDefault);

    const previousExpanded = useLastValue(isExpanded);

    const ref = useRef<HTMLDivElement>(null);
    const measurements = useMeasure(ref);

    const toggleCollapse = () => {
        if (isExpanded) {
            setIsExpanded(false);
            if (ref.current) {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                window.scrollTo({ top: ref.current.getBoundingClientRect().top + scrollTop, behavior: "smooth" });
            }
        } else {
            setIsExpanded(true);
        }
    };
    const { height } = useSpring({
        height: isExpanded ? measurements.height : Math.min(measurements.height, props.maxHeight),
    });

    const classes = collapsableContentClasses();

    return (
        <animated.div
            style={{
                height: isExpanded && previousExpanded === isExpanded ? "auto" : height,
            }}
            className={classNames(classes.root)}
        >
            <div ref={ref} className={props.className}>
                {props.children}
            </div>
            {measurements.height > props.maxHeight && (
                <Button className={classes.collapser} baseClass={ButtonTypes.ICON} onClick={toggleCollapse}>
                    <ChevronUpIcon className={classes.collapserIcon} rotate={isExpanded ? undefined : 180} />
                </Button>
            )}
        </animated.div>
    );
}
