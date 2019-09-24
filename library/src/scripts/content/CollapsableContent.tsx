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
import { Transition } from "react-spring/renderprops-universal";
import { nextTick } from "q";

interface IProps {
    children: React.ReactNode;
    maxHeight: number;
    className?: string;
    isExpandedDefault: boolean;
}

export function CollapsableContent(props: IProps) {
    const { isExpandedDefault } = props;
    const [isExpanded, setIsExpanded] = useState(isExpandedDefault);

    // const previousExpanded = useLastValue(isExpanded);

    const ref = useRef<HTMLDivElement>(null);
    const scrollRef = useRef<HTMLDivElement>(null);
    const measurements = useMeasure(ref);

    useLayoutEffect(() => {
        nextTick(() => {
            scrollRef.current!.scrollTo({ top: 0 });
        });
    });

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

    const maxCollapsedHeight = measurements.height < props.maxHeight ? measurements.height : props.maxHeight;
    const targetHeight = isExpanded ? measurements.height : maxCollapsedHeight;

    const { height } = useSpring({
        height: targetHeight,
    });

    const classes = collapsableContentClasses();

    const hasOverflow = measurements.height > props.maxHeight;

    return (
        <div className={classes.root}>
            <animated.div
                ref={scrollRef}
                style={{
                    minHeight: targetHeight,
                    height: height,
                }}
                className={classNames(classes.heightContainer)}
            >
                <div ref={ref} className={props.className}>
                    {props.children}
                </div>
            </animated.div>

            {hasOverflow && (
                <Button className={classes.collapser} baseClass={ButtonTypes.ICON} onClick={toggleCollapse}>
                    <ChevronUpIcon className={classes.collapserIcon} rotate={isExpanded ? undefined : 180} />
                </Button>
            )}
        </div>
    );
}
