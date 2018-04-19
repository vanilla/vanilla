/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import debounce from "lodash/debounce";
import Quill, { RangeStatic } from "quill/core";
import Emitter from "quill/core/emitter";
import { CLOSE_FLYOUT_EVENT } from "../Quill/utility";
import { withEditor, IEditorContextProps } from "./ContextProvider";

interface IProps extends IEditorContextProps {
    forceVisibility: string;
    setVisibility(value: boolean): void;
}

interface IState {
    range: RangeStatic | null;
    hideSelf: boolean;
}

export class SelectionPositionToolbarContainer extends React.Component<IProps, IState> {
    private quill: Quill;
    private selfNode: HTMLElement;
    private nub: HTMLElement;

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;

        this.state = {
            range: null,
            hideSelf: false,
        };
        this.props.setVisibility(true);
        this.handleEditorChange = this.handleEditorChange.bind(this);
    }

    public render() {
        const x = this.getXCoordinates();
        const y = this.getYCoordinates();
        let toolbarStyles: React.CSSProperties = {
            visibility: "hidden",
            position: "absolute",
        };
        let nubStyles = {};
        let classes = "richEditor-inlineToolbarContainer richEditor-toolbarContainer ";

        if (
            (x && y && !this.state.hideSelf && this.props.forceVisibility === "ignore") ||
            this.props.forceVisibility === "visible"
        ) {
            toolbarStyles = {
                position: "absolute",
                top: y ? y.toolbarPosition : 0,
                left: x ? x.toolbarPosition : 0,
                zIndex: 5,
                visibility: "visible",
            };

            nubStyles = {
                left: x ? x.nubPosition : 0,
                top: y ? y.nubPosition : 0,
            };

            classes += y && y.nubPointsDown ? "isUp" : "isDown";
        }

        return (
            <div className={classes} style={toolbarStyles} ref={ref => (this.selfNode = ref as HTMLElement)}>
                {this.props.children}
                <div style={nubStyles} className="richEditor-nubPosition" ref={nub => (this.nub = nub as HTMLElement)}>
                    <div className="richEditor-nub" />
                </div>
            </div>
        );
    }

    /**
     * Mount quill listeners.
     */
    public componentDidMount() {
        this.quill.on(Emitter.events.EDITOR_CHANGE, this.handleEditorChange);
        document.addEventListener(CLOSE_FLYOUT_EVENT, this.hideSelf);
        window.addEventListener("resize", this.windowResizeListener);
    }

    /**
     * Be sure to remove the listeners when the component unmounts.
     */
    public componentWillUnmount() {
        this.quill.off(Quill.events.EDITOR_CHANGE, this.handleEditorChange);
        document.removeEventListener(CLOSE_FLYOUT_EVENT, this.hideSelf);
        window.removeEventListener("resize", this.windowResizeListener);
    }

    /**
     * Force update on window resize.
     *
     * @returns {Function} - The debounced update function.
     */
    private windowResizeListener = () => debounce(this.forceUpdate, 200);

    private unHideSelf = () => {
        this.setState({
            hideSelf: false,
        });
        this.props.setVisibility(true);
    };

    private hideSelf = () => {
        this.setState({
            hideSelf: true,
        });
        this.props.setVisibility(false);
    };

    /** SECTION: position */

    /**
     * Handle changes from the editor.
     *
     * @param {string} type - The event type. See {quill/core/emitter}
     * @param {RangeStatic} range - The new range.
     * @param {RangeStatic} oldRange - The old range.
     * @param {Sources} source - The source of the change.
     */
    private handleEditorChange(type, range, oldRange, source) {
        if (type !== Emitter.events.SELECTION_CHANGE) {
            return;
        }

        if (range && range.length > 0 && source === Emitter.sources.USER) {
            const content = this.quill.getText(range.index, range.length);
            const isNewLinesOnly = /(\n){1,}/.test(content);

            if (!isNewLinesOnly) {
                this.setState({
                    range,
                });
                this.unHideSelf();
            }
        } else {
            if (this.props.forceVisibility === "visible") {
                return;
            }

            this.setState({
                range: null,
            });
        }
    }

    /**
     * Get the bounds for the current range.
     *
     * @returns {BoundsStatic} The current quill bounds.
     */
    private getBounds() {
        const { range } = this.state;
        if (!range) {
            return null;
        }

        const numLines = this.quill.getLines(range.index, range.length);
        let bounds;

        if (numLines.length === 1) {
            bounds = this.quill.getBounds(range.index, range.length);
        } else {
            // If multi-line we want to position at the center of the last line's selection.
            const lastLine = numLines[numLines.length - 1];
            const index = this.quill.getIndex(lastLine);
            const length = Math.min(lastLine.length() - 1, range.index + range.length - index);
            bounds = this.quill.getBounds(index, length);
        }

        return bounds;
    }

    /**
     * Calculate the X coordinates for the toolbar and it's nub.
     *
     * @param {HTMLElement} toolbar - The toolbar to calculate the position off.
     *
     * @returns {Object} - The X coordinates.
     * @property {number} toolbarPosition
     * @property {number} nubPosition
     */
    private getXCoordinates() {
        const bounds = this.getBounds();
        if (!bounds) {
            return null;
        }

        const containerSize = this.quill.root.offsetWidth;
        const selfSize = this.selfNode.offsetWidth;
        const padding = -6;
        const start = bounds.left;
        const end = bounds.right;

        const halfToolbarSize = selfSize / 2;
        const min = halfToolbarSize + padding;
        const max = containerSize - halfToolbarSize - padding;
        const averageOffset = Math.round((start + end) / 2);

        const toolbarPosition = Math.max(min, Math.min(max, averageOffset)) - halfToolbarSize;
        const nubPosition = averageOffset - toolbarPosition;

        return {
            toolbarPosition,
            nubPosition,
        };
    }

    /**
     * Calculate the Y coordinates for the toolbar and it's nub.
     *
     * @returns {Object} - The Y coordinates.
     * @property {number} toolbarPosition
     * @property {number} nubPosition
     * @property {boolean} nubPointsDown
     */
    private getYCoordinates() {
        const bounds = this.getBounds();
        if (!bounds) {
            return null;
        }

        const offset = 0;
        let toolbarPosition = bounds.top - this.selfNode.offsetHeight - offset;
        let nubPosition = this.selfNode.offsetHeight;
        let nubPointsDown = true;

        const isNearStart = bounds.top < 30;
        if (isNearStart) {
            toolbarPosition = bounds.bottom + offset;
            nubPosition = 0 - this.nub.offsetHeight / 2;
            nubPointsDown = false;
        }

        return {
            toolbarPosition,
            nubPosition,
            nubPointsDown,
        };
    }
}

export default withEditor<IProps>(SelectionPositionToolbarContainer);
