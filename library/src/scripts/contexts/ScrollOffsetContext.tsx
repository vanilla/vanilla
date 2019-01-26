/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { logWarning } from "@library/utility";
import React from "react";
import { style } from "typestyle";

type ScollOffsetSetter = (offset: number) => void;

interface IContextParams {
    setScrollOffset: ScollOffsetSetter;
    resetScrollOffset: () => void;
    scrollOffset: number | null;
    offsetClass: string;
}

export const ScrollOffsetContext = React.createContext<IContextParams>({
    setScrollOffset: () => {
        logWarning("Set scroll offset called, but a proper provider was not configured.");
    },
    resetScrollOffset: () => {
        logWarning("Set scroll offset called, but a proper provider was not configured.");
    },
    scrollOffset: null,
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
}

export class ScrollOffsetProvider extends React.Component<IProps, IState> {
    public state: IState = {
        scrollOffset: 0,
        isScrolledOff: false,
    };

    public render() {
        if (!this.props.scrollWatchingEnabled) {
            return this.props.children;
        }

        const { scrollOffset, isScrolledOff } = this.state;
        const offsetClass = style({
            transition: "transform 0.3s ease",
            willChange: "transform",
            transform: isScrolledOff ? `translateY(-${scrollOffset}px)` : "none",
        });

        return (
            <ScrollOffsetContext.Provider
                value={{
                    setScrollOffset: this.setScrollOffset,
                    resetScrollOffset: this.resetScrollOffset,
                    scrollOffset: isScrolledOff ? scrollOffset : 0,
                    offsetClass,
                }}
            >
                {this.props.children}
            </ScrollOffsetContext.Provider>
        );
    }

    public componentDidMount() {
        window.addEventListener("scroll", this.scrollHandler);
    }

    public componentWillUnmount() {
        window.removeEventListener("scroll", this.scrollHandler);
    }

    private previousScrollValue = 0;

    private scrollHandler = () => {
        if (!this.props.scrollWatchingEnabled) {
            return;
        }

        requestAnimationFrame(() => {
            const wiggleRoom = 10;
            const newScrolledValue = window.scrollY;
            const isScrollingDown = newScrolledValue > this.previousScrollValue + wiggleRoom;
            const isScrollingUp = newScrolledValue < this.previousScrollValue - wiggleRoom;
            this.previousScrollValue = window.scrollY;
            if (isScrollingDown) {
                document.body.classList.toggle("vanillaHeaderIsScolledOff", true);
                this.setState({ isScrolledOff: true });
            } else if (isScrollingUp) {
                document.body.classList.toggle("vanillaHeaderIsScolledOff", false);
                this.setState({ isScrolledOff: false });
            }
        });
    };

    private resetScrollOffset = () => {
        this.setState({
            scrollOffset: 0,
            isScrolledOff: false,
        });
    };

    private setScrollOffset: ScollOffsetSetter = offset => {
        this.setState({ scrollOffset: offset });
    };
}
