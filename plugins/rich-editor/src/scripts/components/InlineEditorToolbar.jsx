/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import Quill from "quill";
import Events from "@core/events";
import EditorToolbar from "./EditorToolbar";
import LinkToolbar from "./LinkToolbar";
import Emitter from "quill/core/emitter";
import { Range } from "quill/core/selection";
import Keyboard from "quill/modules/keyboard";

export default class InlineEditorToolbar extends React.Component {
    static propTypes = {
        quill: PropTypes.instanceOf(Quill).isRequired,
    };

    /** @type {Quill} */
    quill;

    /**
     * @type {Object}
     * @property {RangeStatic} - The current quill selected text range..
     */
    state;

    /** @type {HTMLElement} */
    toolbar;

    /** @type {HTMLElement} */
    nub;

    /** @type {number} */
    resizeListener;

    /** @type {Object<string, MenuItemData>} */
    menuItems = {
        bold: {
            active: false,
        },
        italic: {
            active: false,
        },
        strike: {
            active: false,
        },
        code: {
            active: false,
        },
        link: {
            active: false,
            value: "",
            formatter: () => {
                this.quill.format("link", true, Emitter.sources.USER);
                this.setState({showLink: true});
            },
        },
    };

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;

        this.state = {
            range: null,
            showLink: false,
        };

        this.handleEditorChange = this.handleEditorChange.bind(this);
    }

    /**
     * Mount quill listeners.
     */
    componentDidMount() {
        this.quill.on(Emitter.events.EDITOR_CHANGE, this.handleEditorChange);

        this.resizeListener = Events.addResizeListener(() => {
            this.forceUpdate();
        });
    }

    /**
     * Be sure to remove the listeners when the component unmounts.
     */
    componentWillUnmount() {
        this.quill.off(Quill.events.EDITOR_CHANGE, this.handleEditorChange);
        Events.removeResizeListener(this.resizeListener);
    }

    /** SECTION: position */

    /**
     * Handle changes from the editor.
     *
     * @param {string} type - The event type. See {quill/core/emitter}
     * @param {RangeStatic} range - The new range.
     * @param {RangeStatic} oldRange - The old range.
     * @param {Sources} source - The source of the change.
     */
    handleEditorChange(type, range, oldRange, source) {
        if (type !== Emitter.events.SELECTION_CHANGE) {
            return;
        }

        if (range && range.length > 0 && source === Emitter.sources.USER) {
            this.setState({
                range,
            });
        } else if (!this.state.showLink) {
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
    getBounds() {
        const { range } = this.state;
        if (!range) {
            return null;
        }

        const numLines = this.quill.getLines(range.index, range.length);
        let bounds;

        if (numLines.length === 1) {
            bounds = this.quill.getBounds(range);
        } else {

            // If multi-line we want to position at the center of the last line's selection.
            const lastLine = numLines[numLines.length - 1];
            const index = this.quill.getIndex(lastLine);
            const length = Math.min(lastLine.length() - 1, range.index + range.length - index);
            bounds = this.quill.getBounds(new Range(index, length));
        }

        return bounds;
    }

    /**
     * Calculate the X coordinates for the toolbar and it's nub.
     *
     * @returns {Object} - The X coordinates.
     * @property {number} toolbarPosition
     * @property {number} nubPosition
     */
    getXCoordinates() {
        const bounds = this.getBounds();
        if (!bounds) {
            return null;
        }

        const containerSize = this.quill.root.offsetWidth;
        const selfSize = this.toolbar.offsetWidth;
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
    getYCoordinates() {
        const bounds = this.getBounds();
        if (!bounds) {
            return null;
        }

        const offset = 0;
        let toolbarPosition = bounds.top - this.toolbar.offsetHeight - offset;
        let nubPosition = this.toolbar.offsetHeight;
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

    /**
     * asdf
     *
     * @param {React.KeyboardEvent} event - asdf
     */
    onLinkKeyDown = (event) => {
        if (Keyboard.match(event.nativeEvent, "Enter")) {
            event.preventDefault();
            const value = event.target.value || "";
            this.quill.format('link', value, Emitter.sources.USER);
            this.setState({
                showLink: false,
                range: null,
            });
        }
    };

    /**
     * @inheritDoc
     */
    render() {
        const x = this.getXCoordinates();
        const y = this.getYCoordinates();
        let toolbarStyles = {
            visibility: "hidden",
            position: "absolute",
        };
        let nubStyles = {};
        let classes = "richEditor-inlineMenu ";

        if (x && y) {
            toolbarStyles = {
                position: "absolute",
                top: y.toolbarPosition,
                left: x.toolbarPosition,
                zIndex: 5,
                visibility: "visible",
            };

            nubStyles = {
                left: x.nubPosition,
                top: y.nubPosition,
            };

            classes += y.nubPointsDown ? "isUp" : "isDown";
        }

        const currentToolbar = this.state.showLink
            ? <LinkToolbar keyDownHandler={this.onLinkKeyDown}/>
            : <EditorToolbar menuItems={this.menuItems} quill={this.quill}/>    ;

        return <div className={classes} style={toolbarStyles} ref={(toolbar) => this.toolbar = toolbar}>
            {currentToolbar}
            <div style={nubStyles} className="richEditor-nubPosition" ref={(nub) => this.nub = nub}>
                <div className="richEditor-nub"/>
            </div>
        </div>;
    }
}
