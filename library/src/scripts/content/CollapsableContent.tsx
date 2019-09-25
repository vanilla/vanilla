/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useRef, useLayoutEffect, useMemo } from "react";
import { collapsableContentClasses } from "@library/content/collapsableContentStyles";
import { DownTriangleIcon, ChevronUpIcon, BottomChevronIcon } from "@library/icons/common";
import classNames from "classnames";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { useMeasure, useLastValue } from "@vanilla/react-utils";
import { useSpring } from "react-spring";
import { animated } from "react-spring";
import { Transition } from "react-spring/renderprops-universal";
import { nextTick } from "q";
import { t } from "@library/utility/appUtils";
import { getRequiredID, uniqueIDFromPrefix } from "@library/utility/idUtils";
import { unit } from "@library/styles/styleHelpers";

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
    const maxHeight = maxCollapsedHeight > measurements.height ? measurements.height : maxCollapsedHeight;

    const { height } = useSpring({
        height: targetHeight,
    });

    const gradientProps = useSpring({
        height: !isExpanded ? 100 : 0,
    });

    const classes = collapsableContentClasses();

    const hasOverflow = measurements.height > props.maxHeight;

    const title = isExpanded ? t("Collapse") : t("Expand");

    const toggleID = useMemo(() => uniqueIDFromPrefix("collapsableContent_toggle"), []);
    const contentID = useMemo(() => uniqueIDFromPrefix("collapsableContent_content"), []);

    return (
        <div className={classes.root}>
            <animated.div
                id={contentID}
                ref={scrollRef}
                style={{
                    minHeight: maxHeight,
                    height: height,
                }}
                className={classNames(classes.heightContainer)}
                aria-expanded={isExpanded}
                aria-controlledBy={toggleID}
            >
                <div ref={ref} className={props.className}>
                    {props.children}
                </div>
            </animated.div>

            {hasOverflow && (
                <div className={classes.footer}>
                    <animated.div
                        style={{
                            height: unit(gradientProps.height),
                        }}
                        className={classNames(classes.gradient)}
                    />
                    <Button
                        id={toggleID}
                        title={title}
                        className={classes.collapser}
                        baseClass={ButtonTypes.CUSTOM}
                        onClick={toggleCollapse}
                        controls={contentID}
                    >
                        <BottomChevronIcon
                            title={title}
                            className={classes.collapserIcon}
                            rotate={!isExpanded ? undefined : 180}
                        />
                    </Button>
                </div>
            )}
        </div>
    );
}
