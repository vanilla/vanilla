/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import debounce from "lodash/debounce";
import Quill, { RangeStatic, DeltaStatic, Sources, BoundsStatic } from "quill/core";
import Emitter from "quill/core/emitter";
import { CLOSE_FLYOUT_EVENT } from "../Quill/utility";
import { withEditor, IEditorContextProps } from "./ContextProvider";

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
}

interface IState {
    selectionIndex: number | null;
    selectionLength: number | null;
    quillWidth: number;
}

class QuillFlyoutBounds extends React.PureComponent<IProps, IState> {
    private quill: Quill;

    constructor(props) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;

        this.state = {
            selectionIndex: null,
            selectionLength: null,
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
        this.quill.on(Emitter.events.EDITOR_CHANGE, this.handleEditorChange);
        window.addEventListener("resize", this.windowResizeListener);
    }

    /**
     * Be sure to remove the listeners when the component unmounts.
     */
    public componentWillUnmount() {
        this.quill.off(Quill.events.EDITOR_CHANGE, this.handleEditorChange);
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
     * Handle changes from the editor.
     */
    private handleEditorChange = (
        type: string,
        rangeOrDelta: RangeStatic | DeltaStatic,
        oldRangeOrDelta: RangeStatic | DeltaStatic,
        source: Sources,
    ) => {
        const isTextOrSelectionChange = type === Quill.events.SELECTION_CHANGE || type === Quill.events.TEXT_CHANGE;
        if (source === Quill.sources.SILENT || !isTextOrSelectionChange) {
            return;
        }
        let selection: RangeStatic | null = this.quill.getSelection();
        if (this.props.selectionTransformer && selection) {
            selection = this.props.selectionTransformer(selection);
        }

        if (selection && selection.length > 0) {
            const content = this.quill.getText(selection.index, selection.length);
            const isNewLinesOnly = !content.match(/[^\n]/);

            if (!isNewLinesOnly) {
                this.setState({ selectionIndex: selection.index, selectionLength: selection.length });
                return;
            }
        }

        this.setState({
            selectionIndex: null,
            selectionLength: null,
        });
    };

    /**
     * Get the bounds for the current range.
     *
     * @returns The current quill bounds.
     */
    private getBounds(): BoundsStatic | null {
        const { selectionIndex, selectionLength } = this.state;
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

export default withEditor<IProps>(QuillFlyoutBounds);
