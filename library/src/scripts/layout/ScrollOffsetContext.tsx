/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext } from "react";
import { logWarning } from "@vanilla/utils";
import { style } from "typestyle";

type ScollOffsetSetter = (offset: number) => void;

interface IContextParams {
    setScrollOffset: ScollOffsetSetter;
    resetScrollOffset: () => void;
    scrollOffset: number | null;
    offsetClass: string;
    getCalcedHashOffset(): number;
    hashOffsetRef: React.RefObject<HTMLDivElement>;
    temporarilyDisabledWatching: (duration: number) => void;
}

export const ScrollOffsetContext = React.createContext<IContextParams>({
    setScrollOffset: () => {
        logWarning("Set scroll offset called, but a proper provider was not configured.");
    },
    resetScrollOffset: () => {
        logWarning("Reset scroll offset called, but a proper provider was not configured.");
    },
    scrollOffset: null,
    getCalcedHashOffset: () => 0,
    temporarilyDisabledWatching: () => {
        logWarning("Attempted to disable watching but a proper provider was not configured.");
    },
    hashOffsetRef: {
        current: null,
    },
    offsetClass: "",
});

export interface IWithScrollOffset {
    setScrollOffset: ScollOffsetSetter;
}

interface IProps {
    scrollWatchingEnabled?: boolean;
    children: React.ReactNode;
}

interface IState {
    scrollOffset: number;
    isScrolledOff: boolean;
    hashOffset: number;
    isWatchingEnabled: boolean;
}

/**
 * Provider for handling a global scroll offset.
 * This wraps `ScrollOffset.Provider` with some good default behaviour.
 *
 * Using this, you can have one component declare an offset value.
 * Other components can then recieve this value through context.
 * The context itself handles watching the scroll position and provides a CSS Class styling for the offset.
 *
 * Using the CSS class provided out of this context will translate the set value into translateY value.
 * @see setScrollOffset
 */
export class ScrollOffsetProvider extends React.Component<IProps, IState> {
    public state: IState = {
        scrollOffset: 0,
        isScrolledOff: false,
        hashOffset: 0,
        isWatchingEnabled: true,
    };

    private hashOffsetRef = React.createRef<HTMLDivElement>();

    /**
     * @inheritdoc
     */
    public render() {
        const { scrollWatchingEnabled } = this.props;

        // Generate a CSS based on our calculated values.
        const { scrollOffset, isScrolledOff } = this.state;
        const offsetClass = style({
            transition: "transform 0.3s ease",
            willChange: "transform",
            transform: isScrolledOff ? `translateY(-${scrollOffset}px)` : "none",
        });

        // Render out the context with all values and methods.
        return (
            <ScrollOffsetContext.Provider
                value={{
                    setScrollOffset: this.setScrollOffset,
                    resetScrollOffset: this.resetScrollOffset,
                    scrollOffset: isScrolledOff && scrollWatchingEnabled ? scrollOffset : 0,
                    offsetClass: scrollWatchingEnabled ? offsetClass : "",
                    getCalcedHashOffset: this.getCalcedHashOffset,
                    hashOffsetRef: this.hashOffsetRef,
                    temporarilyDisabledWatching: this.temporarilyDisabledWatching,
                }}
            >
                {this.props.children}
            </ScrollOffsetContext.Provider>
        );
    }

    /**
     * @inheritdoc
     */
    public componentDidMount() {
        window.addEventListener("scroll", this.scrollHandler);
    }

    /**
     * @inheritdoc
     */
    public componentWillUnmount() {
        window.removeEventListener("scroll", this.scrollHandler);
    }

    private get shouldWatchScroll() {
        return this.props.scrollWatchingEnabled && this.state.isWatchingEnabled;
    }

    private temporarilyDisabledWatching = (duration: number) => {
        this.setState({ isWatchingEnabled: false });
        setTimeout(() => {
            this.setState({ isWatchingEnabled: true });
        }, duration);
    };

    /** Keep a local copy of our previous window scroll value. */
    private previousScrollValue = 0;

    private scrollHandler = () => {
        // Early bailout if we aren't watching scroll position.
        if (!this.shouldWatchScroll) {
            return;
        }

        // Trigger the scrolled state if we've moved up or down by a certain number of pixels.
        requestAnimationFrame(() => {
            const wiggleRoom = 10;
            const newScrolledValue = window.scrollY;

            // Always show if we are within the initial scrolloffset.
            if (newScrolledValue < 400) {
                this.setState({ isScrolledOff: false });
                return;
            }

            const isScrollingDown = newScrolledValue > this.previousScrollValue + wiggleRoom;
            const isScrollingUp = newScrolledValue < this.previousScrollValue - wiggleRoom;
            this.previousScrollValue = window.scrollY;
            if (isScrollingDown) {
                this.setState({ isScrolledOff: true });
            } else if (isScrollingUp) {
                this.setState({ isScrolledOff: false });
            }
        });
    };

    /**
     * Reset the context state.
     */
    private resetScrollOffset = () => {
        this.setState({
            scrollOffset: 0,
            isScrolledOff: false,
        });
    };

    /**
     * Set the value items will be translated by.
     */
    private setScrollOffset: ScollOffsetSetter = offset => {
        this.setState({ scrollOffset: offset });
    };

    private getCalcedHashOffset = (): number => {
        const offsetElement = this.hashOffsetRef.current;
        if (!offsetElement) {
            return 0;
        }

        const rect = offsetElement.getBoundingClientRect();
        return rect.bottom;
    };
}

export function useScrollOffset() {
    return useContext(ScrollOffsetContext);
}

/**
 * Component that reports it's bottom most position to the scroll offset provider.
 *
 * Eg. If you click a link #some-hash and an element with the ID `some-hash` is present in the page
 * that item will stay below the HashOffsetReporter.
 */
export function HashOffsetReporter(props: { children: React.ReactNode }) {
    const offset = useScrollOffset();
    return <div ref={offset.hashOffsetRef}>{props.children}</div>;
}
