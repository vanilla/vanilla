/*
 * @author Carla França <cfranca@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
export function getBreakPoints(sliderWrapperWidth: number, maxSlidesToShow?: number) {
    //review mumber of slides after adding negative margin to slideWarpper
    let currentBreakPoint;

    const breakPoints = [
        { width: 1, slidesToShow: 1 },
        { width: 550, slidesToShow: maxSlidesToShow ? Math.min(maxSlidesToShow, 2) : 2 },
        { width: 770, slidesToShow: maxSlidesToShow ? Math.min(maxSlidesToShow, 3) : 3 },
        { width: 1000, slidesToShow: maxSlidesToShow ? Math.min(maxSlidesToShow, 4) : 4 },
        { width: 1100, slidesToShow: maxSlidesToShow ? Math.min(maxSlidesToShow, 5) : 5 },
    ];

    currentBreakPoint = breakPoints
        .slice()
        .reverse()
        .find((bp) => bp.width <= (sliderWrapperWidth ? sliderWrapperWidth : 0));

    return { ...currentBreakPoint };
}
