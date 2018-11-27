/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Quill, { BoundsStatic, RangeStatic } from "quill/core";
import { Omit } from "react-redux";

interface IProviderProps {
    quill: Quill;
    container: HTMLElement | null;
    verticalOffset: number;
    isScrolling: boolean;
}

interface IQuillProps {
    quill: Quill;
}

interface IBounds extends BoundsStatic {
    isScrolledOff: boolean;
}

export interface IWithBoundsProps extends IProviderProps {
    getBounds: (range: RangeStatic) => IBounds;
    scrollOffset: number;
    scrollWidth: number;
    scrollHeight: number;
}

interface IProps extends IProviderProps {
    children: (props: IWithBoundsProps) => React.ReactNode;
}

interface IState {
    scrollPosition: number;
}

const BoundsContext = React.createContext<IProviderProps>({
    container: null,
    verticalOffset: 0,
    quill: {} as any,
    isScrolling: false,
});
export default BoundsContext.Provider;

class BoundsCalculator extends React.PureComponent<IProps, IState> {
    public state: IState = {
        scrollPosition: 0,
    };

    public render() {
        if (this.props.container === null) {
            return null;
        } else {
            return this.props.children(this.getChildProps());
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

    private calculateBounds = (range: RangeStatic): IBounds => {
        const { quill, container, verticalOffset } = this.props;
        const { scrollPosition } = this.state;
        const bounds = quill.getBounds(range.index, range.length);

        bounds.top += verticalOffset - scrollPosition;
        bounds.bottom += verticalOffset - scrollPosition;

        const isScrolledOff = bounds.bottom < 24 || bounds.top > container!.clientHeight + verticalOffset;

        // Enforce minimum top
        bounds.top = Math.max(bounds.top, verticalOffset);
        bounds.bottom = Math.min(bounds.bottom, container!.clientHeight);

        const closerToBottom = bounds.top > container!.clientHeight / 2;

        if (closerToBottom) {
            bounds.top = Math.min(bounds.top, bounds.bottom - bounds.height + verticalOffset);
        }

        const newBounds: IBounds = { ...bounds, isScrolledOff };
        return newBounds;
    };

    private getChildProps(): IWithBoundsProps {
        const scrollOffset = this.props.container!.scrollTop;
        const { container } = this.props;

        return {
            getBounds: this.calculateBounds,
            scrollOffset,
            scrollWidth: container!.scrollWidth,
            scrollHeight: container!.scrollHeight,
            container: this.props.container,
            quill: this.props.quill,
            verticalOffset: this.props.verticalOffset,
            isScrolling: this.props.isScrolling,
        };
    }
}

export function withBounds<T extends IWithBoundsProps = IWithBoundsProps>(WrappedComponent: React.ComponentType<T>) {
    // the func used to compute this HOC's displayName from the wrapped component's displayName.
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    class ComponentWithBounds extends React.Component<Omit<T, keyof IWithBoundsProps> & IQuillProps> {
        public static displayName = `withBounds(${displayName})`;
        public render() {
            return (
                <BoundsContext.Consumer>
                    {providerProps => {
                        return (
                            <BoundsCalculator {...providerProps} {...this.props}>
                                {context => {
                                    return <WrappedComponent {...context} {...this.props} />;
                                }}
                            </BoundsCalculator>
                        );
                    }}
                </BoundsContext.Consumer>
            );
        }
    }

    return ComponentWithBounds;
}
