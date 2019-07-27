/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import debounce from "lodash/debounce";
import { getElementHeight } from "@vanilla/dom-utils";
import { forceRenderStyles } from "typestyle";
import { onContent, removeOnContent } from "@library/utility/appUtils";

interface IProps {
    id: string;
    isCollapsed: boolean;
    preferredMaxHeight: number; // The actual max height could exceed this, but once we pass it stop adding elements.
    setNeedsCollapser?: (needsCollapser: boolean) => void;
    dangerouslySetInnerHTML: {
        __html: string;
    };
}

interface IState {
    maxHeight: number | string;
}

/**
 * A class for dynamic collapsable user content.
 */
export default class CollapsableUserContent extends React.PureComponent<IProps, IState> {
    public state = {
        maxHeight: "100%",
    };
    private selfRef: React.RefObject<HTMLDivElement> = React.createRef();

    public render() {
        const style: React.CSSProperties = { overflow: "hidden", maxHeight: this.state.maxHeight };

        return (
            <div
                id={this.props.id}
                className="collapsableContent userContent"
                style={style}
                ref={this.selfRef}
                dangerouslySetInnerHTML={this.props.dangerouslySetInnerHTML}
            />
        );
    }

    /**
     * Do the initial height calculation and recalcuate if the window dimensions change.
     */
    public componentDidMount() {
        forceRenderStyles();
        this.calcMaxHeight();
        window.addEventListener("resize", this.windowResizerHandler);
        onContent(this.calcMaxHeight);
    }

    /**
     * @inheritDoc
     */
    public componentWillUnmount() {
        window.removeEventListener("resize", this.windowResizerHandler);
        removeOnContent(this.calcMaxHeight);
    }

    /**
     * If certain primary props change we need to recalculate the content height.
     */
    public componentDidUpdate(prevProps: IProps) {
        if (
            prevProps.dangerouslySetInnerHTML.__html !== this.props.dangerouslySetInnerHTML.__html ||
            prevProps.isCollapsed !== this.props.isCollapsed
        ) {
            this.calcMaxHeight();
        }
    }

    /**
     * Handle recalcuating the components max height in a debounced fashion.
     */
    private windowResizerHandler = () =>
        debounce(() => {
            window.requestAnimationFrame(() => this.calcMaxHeight());
        }, 200)();

    /**
     * Calculate the exact pixel max height of the content around the threshold of preferredMaxHeight.
     */
    private getHeightInfo(): {
        height: number | null;
        needsCollapser: boolean;
    } {
        const self = this.selfRef.current;

        if (self === null) {
            return {
                height: null,
                needsCollapser: false,
            };
        }

        if (self.childElementCount <= 1) {
            return {
                height: null,
                needsCollapser: false,
            };
        }

        let finalMaxHeight = 0;
        let lastBottomMargin = 0;
        for (const child of Array.from(self.children)) {
            if (finalMaxHeight > this.props.preferredMaxHeight) {
                return {
                    height: finalMaxHeight,
                    needsCollapser: true,
                };
            }

            const { height, bottomMargin } = getElementHeight(child, lastBottomMargin);
            if (finalMaxHeight > 0 && height > this.props.preferredMaxHeight) {
                finalMaxHeight -= lastBottomMargin;

                return {
                    height: finalMaxHeight,
                    needsCollapser: true,
                };
            }
            lastBottomMargin = bottomMargin;
            finalMaxHeight += height;
        }

        return {
            height: finalMaxHeight,
            needsCollapser: finalMaxHeight > this.props.preferredMaxHeight,
        };
    }

    /**
     * Calculate the CSS max height that we want to apply to the container div.
     */
    private calcMaxHeight = () => {
        const { height, needsCollapser } = this.getHeightInfo();

        let newHeight = needsCollapser && this.props.isCollapsed ? height! : this.selfRef.current!.scrollHeight;
        if (newHeight === 0) {
            // This is probably a mistake. Since we are hidden we don't want to actually re-render.
            return;
        }

        this.setState({ maxHeight: newHeight });
        this.props.setNeedsCollapser && this.props.setNeedsCollapser(needsCollapser);
    };
}
