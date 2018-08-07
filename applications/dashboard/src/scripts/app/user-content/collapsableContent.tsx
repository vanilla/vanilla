/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import debounce from "lodash/debounce";
import { getElementHeight } from "@dashboard/dom";

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
        this.calcMaxHeight();
        window.addEventListener("resize", () =>
            debounce(() => {
                this.calcMaxHeight();
            }, 200)(),
        );
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
     * Determine if we need to display the collapsing toggle or not.
     *
     * If we are always at 100% height it doesn't make sense to show a toggle.
     */
    private needsCollapser(maxHeight: number | null): boolean {
        const self = this.selfRef.current;
        return self !== null && self.childElementCount >= 1 && maxHeight !== null && maxHeight >= 100;
    }

    /**
     * Calculate the exact pixel max height of the content around the threshold of preferredMaxHeight.
     */
    private getNumberMaxHeight(): number | null {
        const self = this.selfRef.current;

        if (!self) {
            return null;
        }

        let finalMaxHeight = 0;
        let lastBottomMargin = 0;
        Array.from(self.children).forEach(child => {
            if (finalMaxHeight > 100) {
                return;
            }

            const { height, bottomMargin } = getElementHeight(child, lastBottomMargin);
            lastBottomMargin = bottomMargin;
            finalMaxHeight += height;
        });
        return finalMaxHeight;
    }

    /**
     * Calculate the CSS max height that we want to apply to the container div.
     */
    private calcMaxHeight() {
        const maxHeight = this.getNumberMaxHeight();
        if (this.needsCollapser(maxHeight) && this.props.isCollapsed) {
            this.setState({ maxHeight: maxHeight! });
        } else {
            this.setState({ maxHeight: this.selfRef.current!.scrollHeight });
        }

        this.props.setNeedsCollapser && this.props.setNeedsCollapser(this.needsCollapser(maxHeight));
    }
}
