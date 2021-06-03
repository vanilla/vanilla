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
    sliderPosition: object;
    slidesWidth: number;
    numberOfSlidesToShow: number;
    slideActiveIndex: number;
}

export function CarouselSlider(props: IProps) {
    const classes = carouselClasses();
    const { sliderWrapperRef, children, sliderPosition, slidesWidth, numberOfSlidesToShow, slideActiveIndex } = props;

    return (
        <div className={classes.sliderWrapper} ref={sliderWrapperRef}>
            <ul className={classes.slider} style={sliderPosition}>
                {Children.map(children, (child, idx) => {
                    if (!React.isValidElement(child)) {
                        return null;
                    }

                    //check if element is visible
                    if (!numberOfSlidesToShow) return;
                    const isTabbable = idx >= slideActiveIndex && idx < slideActiveIndex + numberOfSlidesToShow;
                    //toggle tabIndex (0 is tababble and -1 is not)
                    const childToRender = React.cloneElement(child, {
                        tabIndex: isTabbable ? 0 : -1,
                    });
                    return (
                        <li
                            key={idx}
                            style={{
                                width: slidesWidth,
                            }}
                        >
                            {childToRender}
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}
