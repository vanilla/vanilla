/*
 * @author Carla França <cfranca@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { bindActionCreators, createSlice, PayloadAction } from "@reduxjs/toolkit";
import { useReducer, useMemo } from "react";

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

interface CarouselOptions {
    slidesToShow?: number;
    childWidth?: number;
}

const carouselSlice = createSlice({
    name: "carousel",
    initialState: initCarouselState,
    reducers: {
        prev: (state, action: PayloadAction<IPaginateAction>) => {
            const { desiredIndex, toShow } = action.payload;
            return {
                ...state,
                activeIndex: desiredIndex,
                desiredIndex: desiredIndex - toShow < 0 ? 0 : desiredIndex - toShow,
            };
        },
        next: (state, action: PayloadAction<IPaginateAction>) => {
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

export function useCarousel(slidesLength: number, options: CarouselOptions = {}) {
    const { slidesToShow = 1, childWidth = 0 } = options;
    const [state, dispatch] = useReducer(carouselSlice.reducer, initCarouselState);

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

    const sliderLeftPosiion: React.CSSProperties = { left: `0px` };

    //Update SliderPosition
    let sliderPosition = slidesToShow === 1 ? -20 : 0;

    if (state.desiredIndex > state.activeIndex) {
        //next
        let currentIndex = state.activeIndex + slidesToShow;
        //adjust currentIndex to avoid gap at the end of the slider
        if (currentIndex + slidesToShow > slidesLength) {
            currentIndex = slidesLength - slidesToShow;
        }

        if (slidesToShow === 1 && currentIndex >= 1) {
            sliderPosition = -(childWidth + 16) * currentIndex + (20 / 100) * childWidth;
        } else {
            sliderPosition = -(childWidth + 16) * currentIndex;
        }
    } else {
        //prev
        let currentIndex = state.desiredIndex;

        //adjust sliderPosition when jump to 0
        if (slidesToShow === 1) {
            currentIndex === 0
                ? (sliderPosition = 20)
                : (sliderPosition = -(childWidth + 16) * currentIndex + (20 / 100) * childWidth);
        } else {
            sliderPosition = -(childWidth + 16) * currentIndex;
        }
    }

    sliderLeftPosiion.left = `${sliderPosition}px`;

    return { activeIndex: state.activeIndex, desiredIndex: state.desiredIndex, actions, sliderLeftPosiion };
}
