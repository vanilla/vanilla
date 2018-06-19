/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import debounce from "lodash/debounce";
import Quill, { RangeStatic, DeltaStatic, Sources, BoundsStatic } from "quill/core";
import { withEditor, IEditorContextProps } from "@rich-editor/components/context";

interface IXCoordinates {
    position: number;
    nubPosition?: number;
}

interface IYCoordinates {
    position: number;
    nubPosition?: number;
    nubPointsDown?: boolean;
}

interface IParameters {
    x: IXCoordinates | null;
    y: IYCoordinates | null;
}

type HorizontalAlignment = "center" | "start";
type VerticalAlignment = "above" | "below";

interface IProps extends IEditorContextProps {
    children: (params: IParameters) => JSX.Element;
    selectionTransformer?: (selection: RangeStatic) => RangeStatic | null;
    flyoutHeight: number | null;
    flyoutWidth: number | null;
    nubHeight?: number | null;
    horizontalAlignment?: HorizontalAlignment;
    verticalAlignment?: VerticalAlignment;
    isActive: boolean;
    selection?: RangeStatic | null;
    selectionIndex: number | null;
    selectionLength: number | null;
}

interface IState {
    quillWidth: number;
}

class ToolbarPositioner extends React.PureComponent<IProps, IState> {
    private quill: Quill;

    constructor(props) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;

        this.state = {
            quillWidth: this.quill.root.offsetWidth,
        };
    }

    public render() {
        const params = this.props.isActive
            ? {
                  x: this.getXCoordinates(),
                  y: this.getYCoordinates(),
              }
            : {
                  x: null,
                  y: null,
              };

        return this.props.children(params);
    }

    /**
     * Mount quill listeners.
     */
    public componentDidMount() {
        window.addEventListener("resize", this.windowResizeListener);
    }

    /**
     * Be sure to remove the listeners when the component unmounts.
     */
    public componentWillUnmount() {
        window.removeEventListener("resize", this.windowResizeListener);
    }

    /**
     * Force update on window resize.
     */
    private windowResizeListener = () => {
        const debouncedWidthUpdate = debounce(() => {
            this.setState({ quillWidth: this.quill.root.offsetWidth });
        }, 200);
        debouncedWidthUpdate();
    };

    /**
     * Get the bounds for the current range.
     *
     * @returns The current quill bounds.
     */
    private getBounds(): BoundsStatic | null {
        const { selectionIndex, selectionLength } = this.props;
        if (selectionIndex === null || selectionLength === null) {
            return null;
        }

        const numLines = this.quill.getLines(selectionIndex, selectionLength);
        let bounds;

        if (numLines.length === 1) {
            bounds = this.quill.getBounds(selectionIndex, selectionLength);
        } else {
            // If multi-line we want to position at the center of the last line's selection.
            const lastLine = numLines[numLines.length - 1];
            const index = this.quill.getIndex(lastLine);
            const length = Math.min(lastLine.length() - 1, selectionIndex + selectionLength - index);
            bounds = this.quill.getBounds(index, length);
        }

        return bounds;
    }

    /**
     * Calculate the X coordinates for the toolbar and it's nub.
     */
    private getXCoordinates(): IXCoordinates | null {
        const bounds = this.getBounds();
        if (!bounds || !this.props.flyoutWidth) {
            return null;
        }

        const quillWidth = this.state.quillWidth;
        const { flyoutWidth } = this.props;

        const start = bounds.left;
        const end = bounds.right;

        const alignment = this.props.horizontalAlignment || "center";
        if (alignment === "center") {
            const padding = -6;

            const halfToolbarSize = flyoutWidth / 2;
            const min = halfToolbarSize + padding;
            const max = quillWidth - halfToolbarSize - padding;
            const averageOffset = Math.round((start + end) / 2);

            const position = Math.max(min, Math.min(max, averageOffset)) - halfToolbarSize;
            const nubPosition = averageOffset - position;

            return {
                position,
                nubPosition,
            };
        } else {
            const inset = 6;
            const min = start;
            const max = quillWidth - flyoutWidth - inset;
            const position = Math.min(max, Math.max(min, start));

            return {
                position,
                nubPosition: position,
            };
        }
    }

    /**
     * Calculate the Y coordinates for the toolbar and it's nub.
     */
    private getYCoordinates(): IYCoordinates | null {
        const bounds = this.getBounds();
        if (!bounds || !this.props.flyoutHeight) {
            return null;
        }

        const { flyoutHeight, nubHeight, verticalAlignment } = this.props;

        const offset = 0;
        let position = bounds.top - flyoutHeight - offset;
        let nubPosition = flyoutHeight;
        let nubPointsDown = true;

        const isNearStart = bounds.top < 30;
        if (isNearStart || verticalAlignment === "below") {
            position = bounds.bottom + offset;

            if (nubHeight) {
                nubPosition = 0 - nubHeight / 2;
                nubPointsDown = false;
            }
        }

        return {
            position,
            nubPosition,
            nubPointsDown,
        };
    }
}

export default withEditor<IProps>(ToolbarPositioner);
