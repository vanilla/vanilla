/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { collapsableContentClasses } from "@library/content/collapsableContentStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { BottomChevronIcon } from "@library/icons/common";
import { t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { useMeasure } from "@vanilla/react-utils";
import classNames from "classnames";
import { nextTick } from "q";
import React, { useLayoutEffect, useMemo, useRef, useState } from "react";
import { animated, useSpring } from "react-spring";
import ReactDOM from "react-dom";

interface IProps {
    /** The maximum collapsed height of the collapser. */
    maxHeight?: number;

    /**
     * A height to allow the content to reach without collapsing.
     * It can be awkward if you size w/ the collapser is actually taller than the if you didn't have one at all.
     *
     * This is basically "wiggle" room to prevent that from occuring.
     **/
    overshoot?: number;

    /** A CSS class to apply to the content area of the collapser. */
    className?: string;

    /** If specified simple CSS classes will be applied to allow external styling. */
    allowsCssOverrides?: boolean;

    /** Whether or not the content area is collapsed by default. */
    isExpandedDefault?: boolean;

    /** React children to apply. */
    children?: React.ReactNode;

    /** An array of DOM nodes to apply as children instead of react contents. See autoWrapCollapsableContent. */
    domNodesToAttach?: Node[];
}

/**
 * Content collapsing react component.
 */
export function CollapsableContent(props: IProps) {
    const { isExpandedDefault = false, domNodesToAttach } = props;
    const [isExpanded, setIsExpanded] = useState(isExpandedDefault);

    const containerMaxHeight = props.maxHeight ? props.maxHeight : 100;
    const containerOvershoot = props.overshoot ? props.overshoot : 50;

    const heightLimit = containerMaxHeight + containerOvershoot;

    const ref = useRef<HTMLDivElement>(null);
    const scrollRef = useRef<HTMLDivElement>(null);
    const measurements = useMeasure(ref);

    // When we mount for the first time copy domNodes over
    // For usage with autoWrapCollapsableContent()
    useLayoutEffect(() => {
        if (domNodesToAttach && ref.current) {
            domNodesToAttach.forEach(node => {
                console.log("append node", node);
                ref.current?.appendChild(node);
            });
        }
    }, []); // eslint-ignore-line

    useLayoutEffect(() => {
        nextTick(() => {
            scrollRef.current!.scrollTo({ top: 0 });
        });
    });

    const toggleCollapse = () => {
        if (isExpanded) {
            if (ref.current) {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const boundingRect = ref.current.getBoundingClientRect();
                const shrinkageHeight =
                    boundingRect.height < containerMaxHeight ? 0 : boundingRect.height - containerMaxHeight;
                const scrollAdjustedByShrinkage = Math.max(scrollTop - shrinkageHeight, 0);

                window.scrollTo({ top: scrollAdjustedByShrinkage, behavior: "smooth" });
            }
            setIsExpanded(false);
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
        <div className={classNames(classes.root, props.allowsCssOverrides && "collapsableContent")}>
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
                    <animated.div
                        style={gradientProps}
                        className={classNames(
                            classes.gradient,
                            props.allowsCssOverrides && "collapsableContent-gradient",
                        )}
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

/**
 * Take any elements that have the class `.js-collapsable` and wrap them in this collapser.
 */
export async function autoWrapCollapsableContent() {
    const jsCollapsables = document.body.querySelectorAll(".js-collapsable");

    return await Promise.all(
        Array.from(jsCollapsables).map((element: HTMLElement) => {
            return new Promise(resolve => {
                const nodes = Array.from(element.childNodes);
                const className = element.getAttribute("data-className") || undefined;

                ReactDOM.render(
                    <CollapsableContent
                        className={className}
                        domNodesToAttach={nodes}
                        allowsCssOverrides
                    ></CollapsableContent>,
                    element,
                    () => resolve(),
                );
                element.classList.remove("js-collapsable");
            });
        }),
    );
}
