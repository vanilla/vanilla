/*
 * @author Carla França <cfranca@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef, useState, useEffect, useMemo } from "react";
import { useMeasure } from "@vanilla/react-utils";
import _debounce from "lodash/debounce";
import _range from "lodash/range";
import { t } from "@library/utility/appUtils";
import { RightChevronSmallIcon, LeftChevronSmallIcon } from "@library/icons/common";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { carouselClasses } from "@library/carousel/Carousel.style";

import { getBreakPoints } from "@library/carousel/CarouselBreakpoints";
import { CarouselSectionSliderWrapper, CarouselSliderWrapper, PagingWrapper } from "@library/carousel/CarouselWrappers";
import { CarouselHeaderAccessibility } from "@library/carousel/CarouselHeaderAccessibility";
import { CarouselSlider } from "@library/carousel/CarouselSlider";
import { CarouselPaging } from "@library/carousel/CarouselPaging";
import { CarouselArrowNav } from "@library/carousel/CarouselArrowNav";

import { useCarousel } from "@library/carousel/useCarousel";
import clamp from "lodash/clamp";

/**
 * Configurable Carousel component.
 *
 * carouselTitle => Implemented for accessibility (default: "Carousel Title" )
 * Items are wrapped in an unordered list (accessibility improvement),
 * Use <Carousel carouselTitle="Accessibility Title">
 *
 */

interface CarouselState {
    activeIndex: number;
    desiredIndex: number;
    currentPagingDot: number;
    numberOfDots: number[];
}

const initCarouselState: CarouselState = {
    activeIndex: 0,
    desiredIndex: 0,
    currentPagingDot: 0,
    numberOfDots: [],
};
interface IProps {
    children: React.ReactNode;
    carouselTitle?: string;
    showPaging?: boolean;
    maxSlidesToShow?: number;
}

export function Carousel(props: IProps) {
    const classes = carouselClasses();
    const { children, carouselTitle = "Carousel Title", showPaging = true } = props;
    const sectionWrapper = useRef<HTMLDivElement>(null);
    const sliderWrapper = useRef<HTMLDivElement>(null);
    const measureSectionWrapper = useMeasure(sectionWrapper);
    const measureSliderWrapper = useMeasure(sliderWrapper);
    const slides = React.Children.toArray(children);
    const maxSlidesToShow = clamp(props.maxSlidesToShow ?? 4, 2, slides.length);

    const toShow: number = getBreakPoints(measureSectionWrapper.width, maxSlidesToShow);

    //Carousel Children Size flex/responsive updating based on sliderWrapper width
    //values to be removed from sliderWrapper width
    //margin-left (16) witch doesnt occur in the first slider
    //padding on each slider (2 on each side) = 4
    const childWidth =
        toShow === 1
            ? measureSliderWrapper.width - (30 / 100) * (measureSliderWrapper.width - 16)
            : (measureSliderWrapper.width - 16 * (toShow - 1) - 4) / toShow;

    //Carousel Sliders position update
    const { activeIndex, desiredIndex, actions, handlers, sliderPosition } = useCarousel(
        slides.length,
        measureSliderWrapper.width,
        {
            toShow,
            childWidth,
            sliderWrapper,
        },
    );

    //PagingDots update currentPagingDot
    const [state, setState] = useState(initCarouselState);

    //On resize
    useEffect(() => {
        setState({ ...state, numberOfDots: _range(0, Math.ceil(slides.length / toShow)) });
        const handleResize = _debounce(() => {
            //Reset Carousel to initial state
            actions.start();
        }, 100);

        window.addEventListener("resize", handleResize);
        return () => window.removeEventListener("resize", handleResize);
    }, [actions, toShow, sliderPosition]);

    //Navigate via Dots
    const handleDotClick = (e) => {
        e.preventDefault();
        const dotIndex = e.currentTarget.dataset.idx;
        let currentIndex = dotIndex * toShow;
        const desiredIndex = dotIndex;

        if (dotIndex >= state.currentPagingDot) {
            actions.pagingNext({ toShow, desiredIndex });

            if (currentIndex + toShow > slides.length) {
                currentIndex = slides.length - toShow;
            }
            setState({
                ...state,
                currentPagingDot: dotIndex ? dotIndex : Math.ceil(Math.abs(activeIndex + toShow) / toShow),
            });
        } else {
            actions.pagingPrev({ toShow, desiredIndex });
            if (dotIndex === 0) setState({ ...state, currentPagingDot: 0 });

            setState({
                ...state,
                currentPagingDot: dotIndex ? dotIndex : Math.ceil(currentIndex / toShow),
            });
        }
    };

    //Navigate via Arrows
    const arrowHandler = (e) => {
        e.preventDefault();
        const direction = e.currentTarget.dataset.direction;
        actions[direction]({ toShow, desiredIndex });
    };

    return (
        <CarouselSectionSliderWrapper sectionWrapperRef={sectionWrapper}>
            <a className={classes.skipCarousel} href="#carouselEnd">
                {t("Skip to end of Carousel")}
            </a>

            <CarouselHeaderAccessibility title={t(`${carouselTitle}`)} />

            <CarouselSliderWrapper
                sliderStyle={{
                    height: sliderWrapper.current ? sliderWrapper.current.children[0].clientHeight + 20 : 0,
                    margin: toShow === 1 ? "0 -20px" : "0 0",
                }}
            >
                {measureSectionWrapper.width >= 765 && state.numberOfDots.length > 1 && (
                    <CarouselArrowNav
                        disabled={desiredIndex === 0}
                        accessibilityLabel={t("Previous Slides")}
                        direction="prev"
                        arrowHandler={arrowHandler}
                        arrowType={<LeftChevronSmallIcon />}
                    />
                )}

                <CarouselSlider
                    sliderWrapperRef={sliderWrapper}
                    childWidth={childWidth ? childWidth : 0}
                    slideDesiredIndex={desiredIndex}
                    numberOfSlidesToShow={toShow}
                    sliderPosition={sliderPosition}
                    enableSwipe={true}
                    preventDefaultTouchmoveEvent={true}
                    enableMouseSwipe={true}
                    handlers={handlers}
                >
                    {children}
                </CarouselSlider>

                {measureSectionWrapper.width >= 765 && state.numberOfDots.length > 1 && (
                    <CarouselArrowNav
                        disabled={desiredIndex + toShow >= slides.length}
                        accessibilityLabel={t("Next Slides")}
                        direction="next"
                        arrowHandler={arrowHandler}
                        arrowType={<RightChevronSmallIcon />}
                    />
                )}
            </CarouselSliderWrapper>
            {showPaging && state.numberOfDots.length > 1 && (
                <PagingWrapper>
                    {measureSectionWrapper.width < 765 && (
                        <CarouselArrowNav
                            disabled={desiredIndex === 0}
                            accessibilityLabel={t("Previous Slides")}
                            direction="prev"
                            arrowHandler={arrowHandler}
                            arrowType={<LeftChevronSmallIcon />}
                        />
                    )}
                    <CarouselPaging
                        numbSlidesToShow={toShow}
                        numberOfDots={state.numberOfDots}
                        slideActiveIndex={desiredIndex}
                        setActiveIndex={handleDotClick}
                    />
                    {measureSectionWrapper.width < 765 && (
                        <CarouselArrowNav
                            disabled={desiredIndex + toShow >= slides.length}
                            accessibilityLabel={t("Next Slides")}
                            direction="next"
                            arrowHandler={arrowHandler}
                            arrowType={<RightChevronSmallIcon />}
                        />
                    )}
                </PagingWrapper>
            )}

            <ScreenReaderContent>
                <div aria-live="polite" aria-atomic="true">
                    {t(`${toShow} Slides on display, initial Slide ${desiredIndex + 1} of ${slides.length}`)}
                </div>
            </ScreenReaderContent>

            <span id="carouselEnd" className="sr-only">
                End of Carousel
            </span>
        </CarouselSectionSliderWrapper>
    );
}
