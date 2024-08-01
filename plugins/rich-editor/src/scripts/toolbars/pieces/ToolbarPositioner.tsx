/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import debounce from "lodash-es/debounce";
import Quill, { BoundsStatic } from "quill/core";
import { IWithEditorProps } from "@rich-editor/editor/context";
import { withEditor } from "@rich-editor/editor/withEditor";
import { richEditorVariables } from "@library/editor/richEditorVariables";

import { IParameters, IXCoordinates, IYCoordinates } from "@library/editor/toolbars/FloatingToolbarContent";
import floatingToolbarVariables from "@library/editor/toolbars/FloatingToolbar.variables";

export type HorizontalAlignment = "center" | "start";
export type VerticalAlignment = "above" | "below";

interface IProps extends IWithEditorProps {
    children: (params: IParameters) => JSX.Element;
    flyoutHeight: number | null;
    flyoutWidth: number | null;
    nubHeight?: number | null;
    horizontalAlignment?: HorizontalAlignment;
    verticalAlignment?: VerticalAlignment;
    isActive: boolean;
    selectionIndex: number | null;
    selectionLength: number | null;
}

interface IState {
    editorWidth: number;
}

class ToolbarPositioner extends React.Component<IProps, IState> {
    private editor: Quill;

    constructor(props: IProps) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.editor = props.editor!;

        this.state = {
            editorWidth: this.editor.root.offsetWidth,
        };
    }

    /**
     * This component is particularly performance sensitive (the calculations for a re-render are very expensive).
     *
     * This implementation should behave like PureComponent.prototype.shouldComponentUpdate() except
     * - It will not recognize changes in selection index when the component is not active.
     */
    public shouldComponentUpdate(nextProps: IProps, nextState) {
        const splitProps = this.extractValuesFromProps(this.props);
        const splitNextProps = this.extractValuesFromProps(nextProps);

        let shouldUpdate = false;

        if (nextProps.isActive && splitProps.selectionIndex !== splitNextProps.selectionIndex) {
            shouldUpdate = true;
        }

        if (nextProps.isActive && splitProps.selectionLength !== splitNextProps.selectionLength) {
            shouldUpdate = true;
        }

        for (const key of Object.keys(splitProps.otherProps)) {
            if (splitProps.otherProps[key] !== splitNextProps.otherProps[key]) {
                shouldUpdate = true;
            }
        }

        if (this.state.editorWidth !== nextState.editorWidth) {
            shouldUpdate = true;
        }

        return shouldUpdate;
    }

    public render() {
        const vars = floatingToolbarVariables();
        const params = this.props.isActive
            ? {
                  x: this.getXCoordinates(this.props.legacyMode ? 0 : -vars.scrollContainer.overshoot),
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

    private extractValuesFromProps(props: IProps) {
        const { selectionIndex, selectionLength, ...otherProps } = props;
        return {
            selectionIndex,
            selectionLength,
            otherProps,
        };
    }

    /**
     * Force update on window resize.
     */
    private windowResizeListener = () => {
        const debouncedWidthUpdate = debounce(() => {
            this.setState({ editorWidth: this.editor.root.offsetWidth });
        }, 200);
        debouncedWidthUpdate();
    };

    /**
     * Get the bounds for the current range.
     *
     * @returns The current quill bounds.
     */
    private getBounds(offsetX?: number, offsetY?: number): BoundsStatic | null {
        const { selectionIndex, selectionLength } = this.props;
        if (selectionIndex === null || selectionLength === null) {
            return null;
        }

        const numLines = this.editor.getLines(selectionIndex, selectionLength);
        let bounds;

        if (numLines.length <= 1) {
            bounds = this.editor.getBounds(selectionIndex, selectionLength);
        } else {
            // If multi-line we want to position at the center of the last line's selection.
            const lastLine = numLines[numLines.length - 1];
            const index = this.editor.getIndex(lastLine);
            const length = Math.min(lastLine.length() - 1, selectionIndex + selectionLength - index);
            bounds = this.editor.getBounds(index, length);
        }
        bounds.y += offsetY;
        bounds.x += offsetX;

        return bounds;
    }

    /**
     * Calculate the X coordinates for the toolbar and it's nub.
     */
    private getXCoordinates(offsetX?: number): IXCoordinates | null {
        const bounds = this.getBounds(offsetX);
        if (!bounds || !this.props.flyoutWidth) {
            return null;
        }

        const editorWidth = this.state.editorWidth;
        const { flyoutWidth } = this.props;

        const start = bounds.left;
        const end = bounds.right;

        const alignment = this.props.horizontalAlignment || "center";
        if (alignment === "center") {
            const padding = -6;

            const halfToolbarSize = flyoutWidth / 2;
            const min = halfToolbarSize + padding;
            const max = editorWidth - halfToolbarSize - padding;
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
            const max = editorWidth - flyoutWidth - inset;
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
        if (bounds == null || this.props.flyoutHeight == null) {
            return null;
        }
        const vars = richEditorVariables();
        const { flyoutHeight, nubHeight, verticalAlignment } = this.props;

        const offset = this.props.legacyMode ? 0 : vars.spacing.paddingTop;
        let position = bounds.top - flyoutHeight + offset;
        let nubPosition = flyoutHeight - 1;
        let nubPointsDown = true;

        const isNearStart = bounds.top <= vars.menuButton.size * 2;
        if (isNearStart || verticalAlignment === "below") {
            position = bounds.bottom + offset;

            if (nubHeight) {
                nubPosition = 1 - nubHeight * 2;
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

export default withEditor(ToolbarPositioner);
