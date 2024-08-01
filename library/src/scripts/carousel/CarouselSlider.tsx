/*
 * @author Carla França <cfranca@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { Children } from "react";
import { carouselClasses } from "@library/carousel/Carousel.style";

/**
 *
 * Items are wrapped in an unordered list (accessibility improvement)
 * UL will have the left property updated to scroll slider
 * LI has the style itemSize to reset the width "responsive"
 *
 */
interface IProps {
    sliderWrapperRef: React.Ref<HTMLDivElement>;
    children: React.ReactNode;
    childWidth: number;
    numberOfSlidesToShow: number;
    slideDesiredIndex: number;
    sliderPosition: number;
    enableSwipe: boolean;
    enableMouseSwipe: boolean;
    preventDefaultTouchmoveEvent: boolean;

    handlers: any;
}

const springConfig = {
    duration: "0.35s",
    easeFunction: "cubic-bezier(0.15, 0.3, 0.25, 1)",
    delay: "0s",
};

const createTransition = (property, options) => {
    const { duration, easeFunction, delay } = options;
    return `${property} ${duration} ${easeFunction} ${delay}`;
};

export function CarouselSlider(props: IProps) {
    const classes = carouselClasses();
    const {
        sliderWrapperRef,
        children,
        handlers,
        childWidth,
        numberOfSlidesToShow,
        slideDesiredIndex,
        sliderPosition,
    } = props;

    const minVisibleItem = slideDesiredIndex;
    const maxVisibleItem = slideDesiredIndex + numberOfSlidesToShow;

    let transition = createTransition("transform", springConfig);
    let WebkitTransition = createTransition("-webkit-transform", springConfig);

    const sliderStyle: React.CSSProperties = {
        WebkitTransition,
        transition,
        transform: `translate(${sliderPosition}px, 0px)`,
    };

    const toRender = Children.map(children, (child, idx) => {
        if (!React.isValidElement<{ tabIndex: number }>(child)) {
            return null;
        }

        const isVisible = idx >= minVisibleItem && idx < maxVisibleItem;
        //check if element is visible
        if (!numberOfSlidesToShow) return;

        //toggle tabIndex (0 is tababble and -1 is not)
        const childToRender = React.cloneElement(child, {
            tabIndex: isVisible ? 0 : -1,
        });
        return <li style={{ width: childWidth }}>{childToRender}</li>;
    });

    return (
        <div className={classes.sliderWrapper} ref={sliderWrapperRef}>
            <div className={classes.slider} style={sliderStyle}>
                <ul className="swipable" {...handlers}>
                    {toRender}
                </ul>
            </div>
        </div>
    );
}
