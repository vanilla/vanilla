/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { collapsableContentClasses } from "@library/content/collapsableContentStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { BottomChevronIcon } from "@library/icons/common";
import { unit } from "@library/styles/styleHelpers";
import { t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { useMeasure } from "@vanilla/react-utils";
import classNames from "classnames";
import { nextTick } from "q";
import React, { useLayoutEffect, useMemo, useRef, useState } from "react";
import { animated, useSpring } from "react-spring";

interface IProps {
    children: React.ReactNode;
    maxHeight?: number;
    overshoot?: number;
    className?: string;
    isExpandedDefault?: boolean;
    firstChild?: boolean;
}

export function CollapsableContent(props: IProps) {
    const { isExpandedDefault = false, firstChild = false } = props;
    const [isExpanded, setIsExpanded] = useState(isExpandedDefault);

    const containerMaxHeight = props.maxHeight ? props.maxHeight : 100;
    const containerOvershoot = props.overshoot ? props.overshoot : 50;

    const heightLimit = containerMaxHeight + containerOvershoot;

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

    const maxCollapsedHeight = measurements.height < heightLimit ? measurements.height : containerMaxHeight;
    const targetHeight = isExpanded ? measurements.height : maxCollapsedHeight;
    const maxHeight = maxCollapsedHeight > measurements.height ? measurements.height : maxCollapsedHeight;

    const { height } = useSpring({
        height: targetHeight > 0 ? targetHeight : "auto",
    });

    const gradientProps = useSpring({
        opacity: isExpanded ? 0 : 1,
    });

    const classes = collapsableContentClasses();

    const hasOverflow = measurements.height > heightLimit;

    const title = isExpanded ? t("Collapse") : t("Expand");

    const toggleID = useMemo(() => uniqueIDFromPrefix("collapsableContent_toggle"), []);
    const contentID = useMemo(() => uniqueIDFromPrefix("collapsableContent_content"), []);

    return (
        <div className={classNames(classes.root)}>
            <animated.div
                id={contentID}
                ref={scrollRef}
                style={{
                    minHeight: maxHeight,
                    height: height,
                }}
                className={classNames(classes.heightContainer)}
                aria-expanded={isExpanded}
            >
                <div ref={ref} className={props.className}>
                    {props.children}
                </div>
            </animated.div>

            {hasOverflow && (
                <div className={classes.footer}>
                    <animated.div style={gradientProps} className={classNames(classes.gradient)} />
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
