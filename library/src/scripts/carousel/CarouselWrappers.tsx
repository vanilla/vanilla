/*
 * @author Carla França <cfranca@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { carouselClasses } from "@library/carousel/Carousel.style";

type ISliderWrapperProps = {
    sliderStyle: object;
    children: React.ReactNode;
};

type ISectionProps = {
    children: React.ReactNode;
    sectionWrapperRef: React.Ref<HTMLDivElement>;
};

export function CarouselSectionSliderWrapper(props: ISectionProps) {
    const classes = carouselClasses();
    const { children, sectionWrapperRef } = props;
    //Need to fix slides when adding negative margin on small size
    return (
        <section ref={sectionWrapperRef} className={classes.sectionWrapper} aria-labelledby="carousel-Title">
            {children}
        </section>
    );
}

export function CarouselSliderWrapper(props: ISliderWrapperProps) {
    const classes = carouselClasses();
    const { children, sliderStyle } = props;
    return (
        <div className={classes.carousel} style={sliderStyle}>
            {children}
        </div>
    );
}

export function PagingWrapper(props) {
    const classes = carouselClasses();
    const { children } = props;
    return <div className={classes.pagingWrapper}>{children}</div>;
}
