/*
 * @author Carla França <cfranca@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { containerVariables } from "@library/layout/components/containerStyles";
import { bindActionCreators, createSlice, PayloadAction } from "@reduxjs/toolkit";
import clamp from "lodash/clamp";
import { useReducer, useMemo, useEffect, useDebugValue } from "react";
import { useSwipeable } from "react-swipeable";
interface CarouselState {
    desiredIndex: number;
    activeIndex: number;
}

const initCarouselState: CarouselState = {
    desiredIndex: 0,
    activeIndex: 0,
};
interface IPaginateAction {
    toShow: number;
    desiredIndex: number;
}
interface INextAction {
    toShow: number;
    desiredIndex: number;
}
interface IPrevAction {
    toShow: number;
    desiredIndex: number;
}
interface CarouselOptions {
    toShow?: number;
    childWidth?: number;
    sliderWrapper?: any;
}

const carouselSlice = createSlice({
    name: "carousel",
    initialState: initCarouselState,
    reducers: {
        prev: (state, action: PayloadAction<IPrevAction>) => {
            const { desiredIndex, toShow } = action.payload;
            return {
                ...state,
                activeIndex: desiredIndex,
                desiredIndex: desiredIndex - toShow < 0 ? 0 : desiredIndex - toShow,
            };
        },
        next: (state, action: PayloadAction<INextAction>) => {
            const { desiredIndex, toShow } = action.payload;
            return { ...state, activeIndex: desiredIndex, desiredIndex: desiredIndex + toShow };
        },
        pagingPrev: (state, action: PayloadAction<IPaginateAction>) => {
            const { desiredIndex, toShow } = action.payload;
            return {
                ...state,
                activeIndex: desiredIndex * toShow + toShow,
                desiredIndex: desiredIndex * toShow,
            };
        },
        pagingNext: (state, action: PayloadAction<IPaginateAction>) => {
            const { desiredIndex, toShow } = action.payload;
            return {
                ...state,
                activeIndex: (desiredIndex - 1) * toShow,
                desiredIndex: desiredIndex * toShow,
            };
        },
        start: (state) => {
            return { ...state, activeIndex: 0, desiredIndex: 0 };
        },
    },
});

export function useCarousel(countSlides: number, sliderWidth: number, options: CarouselOptions = {}) {
    const { toShow = 1, childWidth = 0, sliderWrapper } = options;
    const [state, dispatch] = useReducer(carouselSlice.reducer, initCarouselState);
    let sliderPosition: number = toShow === 1 ? -20 : 0;
    const totalMobileGutter = containerVariables().spacing.mobile.padding * 4;

    const actions = useMemo(
        () =>
            bindActionCreators(
                carouselSlice.actions,
                // Trying to mix together redux and react types is painful, because our redux types include thunk.
                // Reacts dispatch does not support thunk.
                dispatch as any,
            ),
        [dispatch],
    );

    const handlers = useSwipeable({
        onSwiping(e) {
            const { deltaX, absX, dir } = e;

            // If the gesture is horizontal
            if (["left", "right"].includes(dir.toLowerCase())) {
                //Define how far can user swipes
                let distanceSwipe: number = 0;
                const halfSwiped: number = (childWidth * toShow) / 4;

                if (dir === "Left") {
                    distanceSwipe = state.desiredIndex + toShow >= countSlides ? 0 : halfSwiped;
                } else if (dir === "Right") {
                    distanceSwipe = state.desiredIndex === 0 ? 0 : halfSwiped;
                }

                if (absX <= distanceSwipe) {
                    // update swipeable element
                    let swipedSliderPosition: number = sliderPosition + deltaX;
                    const translate: string = `translate(${swipedSliderPosition}px, 0px)`;
                    sliderWrapper.current.children[0].style.WebkitTransform = translate;
                    sliderWrapper.current.children[0].style.translate = translate;
                }
            }
        },
        onSwiped(e) {
            //Stop sliding beyond limits
            if (
                (e.dir === "Right" && state.desiredIndex === 0) ||
                (e.dir === "Left" && state.desiredIndex + toShow >= countSlides) ||
                e.dir === "Down" ||
                e.dir === "Up"
            ) {
                return;
            }

            const { dir } = e;

            if (dir === "Left") {
                const desiredIndex: number = state.desiredIndex;
                actions.next({ toShow, desiredIndex });
            }
            if (dir === "Right") {
                const desiredIndex: number = state.desiredIndex;
                actions.prev({ toShow, desiredIndex });
            }
        },
        trackMouse: true,
        trackTouch: true,
        preventDefaultTouchmoveEvent: true,
    });

    //Update Carousel
    useEffect(() => {}, [state.desiredIndex]);

    //Update sliderPosition
    if (state.desiredIndex > state.activeIndex) {
        //next
        let currentIndex: number = state.activeIndex + toShow;
        //adjust currentIndex to avoid gap at the end of the slider
        if (currentIndex + toShow > countSlides) {
            currentIndex = countSlides - toShow;
        }

        if (toShow === 1 && currentIndex >= 1) {
            sliderPosition = -(childWidth + 16) * currentIndex + (20 / 100) * childWidth;
        } else {
            sliderPosition = -(childWidth + 16) * currentIndex;
        }
    } else {
        //prev
        let currentIndex = state.desiredIndex;

        //adjust sliderLeftPositio when jump to 0
        if (toShow === 1) {
            currentIndex === 0
                ? (sliderPosition = 20)
                : (sliderPosition = -(childWidth + 16) * currentIndex + (20 / 100) * childWidth);
        } else {
            sliderPosition = -(childWidth + 16) * currentIndex;
        }
    }

    // The logic here is pretty ugly but we have a case special when we can only display one item,
    // To prevent us from offsetting past these edges we have clamp the edges.
    //
    //   Display first item with only 1 space to display.
    //   -----------------------------------------|
    //  |   ------------------------   -----------|
    //  |   |                      |   |          |
    //  |   |        Item 1        |   |    Item 2|
    //  |   |                      |   |          |
    //  |   |______________________|   |__________|
    //  |_________________________________________|
    //
    //
    //   Display last item with only 1 space to display.
    //   -----------------------------------------|
    //  |--------    --------------------------   |
    //  |       |   |                          |  |
    //  |Item 8 |   |       Item 9             |  |
    //  |       |   |                          |  |
    //  |_______|   |__________________________|  |
    //  |_________________________________________|

    const minimumOffset = totalMobileGutter;
    const maximumOffset = childWidth * (countSlides - 1) - totalMobileGutter;

    const result = {
        activeIndex: state.activeIndex,
        desiredIndex: state.desiredIndex,
        actions,
        handlers,
        sliderPosition: clamp(sliderPosition, -maximumOffset, minimumOffset),
    };

    useDebugValue({
        result,
        params: {
            minimumOffset,
            maximumOffset,
            childWidth,
            countSlides,
            toShow,
        },
    });

    return result;
}
