/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Quill, { BoundsStatic, RangeStatic } from "quill/core";
import { Omit } from "react-redux";
import debounce from "lodash/debounce";

export interface IWithBoundsProps {
    selectionBounds: BoundsStatic;
    isScrolledOff: boolean;
    scrollOffset: number;
    scrollWidth: number;
    scrollHeight: number;
}

interface IRequiredProps {
    currentSelection: RangeStatic;
    container: HTMLElement | null;
    quill: Quill;
    isScrolling: boolean;
    verticalOffset: number;
}

interface IProps extends IRequiredProps {
    children: (props: IWithBoundsProps) => React.ReactNode;
}

interface IState {
    scrollPosition: number;
}

export default class BoundsProvider extends React.PureComponent<IProps, IState> {
    public state: IState = {
        scrollPosition: 0,
    };

    public render() {
        if (this.props.container === null || this.props.currentSelection === null) {
            return null;
        } else {
            return this.props.children(this.calculateBounds());
        }
    }

    public componentDidMount() {
        this.props.container!.addEventListener("scroll", this.handleScroll);
    }

    public componentWillUnmount() {
        this.props.container!.removeEventListener("scroll", this.handleScroll);
    }

    private handleScroll = (event: Event) => {
        window.requestAnimationFrame(() => {
            this.setState({ scrollPosition: this.props.container!.scrollTop });
        });
    };

    private calculateBounds(): IWithBoundsProps {
        const { quill, currentSelection, container, verticalOffset } = this.props;
        const { scrollPosition } = this.state;
        const initialBounds = quill.getBounds(currentSelection.index, currentSelection.length);
        const newBounds = { ...initialBounds };
        const scrollOffset = container!.scrollTop;

        newBounds.top += verticalOffset - scrollPosition;
        newBounds.bottom += verticalOffset - scrollPosition;

        const isScrolledOff = newBounds.bottom < 24 || newBounds.top > container!.clientHeight + verticalOffset;
        console.log(newBounds, isScrolledOff);

        // Enforce minimum top
        newBounds.top = Math.max(newBounds.top, verticalOffset);
        newBounds.bottom = Math.min(newBounds.bottom, container!.clientHeight);

        const closerToBottom = newBounds.top > container!.clientHeight / 2;

        if (closerToBottom) {
            newBounds.top = Math.min(newBounds.top, newBounds.bottom - newBounds.height + verticalOffset);
        }

        return {
            selectionBounds: newBounds,
            scrollOffset,
            scrollWidth: container!.scrollWidth,
            scrollHeight: container!.scrollHeight,
            isScrolledOff,
        };
    }
}

export function withBounds<T extends IWithBoundsProps = IWithBoundsProps>(WrappedComponent: React.ComponentType<T>) {
    // the func used to compute this HOC's displayName from the wrapped component's displayName.
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    class ComponentWithBounds extends React.Component<IRequiredProps & Omit<T, keyof IWithBoundsProps>> {
        public static displayName = `withBounds(${displayName})`;
        public render() {
            return (
                <BoundsProvider {...this.props}>
                    {context => {
                        return <WrappedComponent {...context} {...this.props} />;
                    }}
                </BoundsProvider>
            );
        }
    }

    return ComponentWithBounds;
}
