/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import ReactDOM from "react-dom";
import debounce from "lodash/debounce";
import { onContent } from "@dashboard/application";
import { getElementHeight } from "@dashboard/dom";

interface IProps {
    id: string;
    isCollapsed: boolean;
    dangerouslySetInnerHTML: {
        __html: string;
    };
}

export default class CollapsableUserContent extends React.PureComponent<IProps> {
    private selfRef: React.RefObject<HTMLDivElement> = React.createRef();

    public render() {
        const style: React.CSSProperties = this.props.isCollapsed
            ? { maxHeight: this.maxHeight, overflow: "hidden" }
            : { maxHeight: this.maxHeight };

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

    public componentDidMount() {
        this.forceUpdate();
        window.addEventListener("resize", () => debounce(() => this.forceUpdate(), 200)());
    }

    private get maxHeight(): number | string {
        const self = this.selfRef.current;
        if (!self) {
            return "100%";
        }

        if (self.childElementCount <= 1) {
            return "100%";
        }

        if (this.props.isCollapsed) {
            let finalMaxHeight = 0;
            let lastBottomMargin = 0;
            Array.from(self.children).forEach((child, index) => {
                if (finalMaxHeight > 100) {
                    return;
                }

                const { height, bottomMargin } = getElementHeight(child, lastBottomMargin);
                lastBottomMargin = bottomMargin;
                finalMaxHeight += height;
            });
            return finalMaxHeight;
        } else {
            return self.scrollHeight;
        }
    }
}
