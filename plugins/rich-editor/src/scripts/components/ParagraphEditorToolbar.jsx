/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import Quill from "quill/core";
import Emitter from "quill/core/emitter";
import EditorToolbar from "./EditorToolbar";
import throttle from "lodash/throttle";
import { pilcro as PilcroIcon } from "./Icons";
import Events from "@core/events";

export default class ParagraphEditorToolbar extends React.PureComponent {
    static propTypes = {
        quill: PropTypes.instanceOf(Quill),
    };

    static initialRange = {
        index: 0,
        length: 0,
    };

    /** @type {Quill} */
    quill;

    /**
     * @type {Object}
     * @property {RangeStatic} - The current quill selected text range..
     */
    state;

    /** @type {HTMLElement} */
    toolbarNode;

    /** @type {HTMLElement} */
    nub;

    /** @type {number} */
    resizeListener;

    menuItems = {
        title: {
            formatName: "header",
            enableValue: 1,
            active: false,
        },
        subtitle: {
            formatName: "header",
            enableValue: 2,
            active: false,
        },
        blockquote: {
            formatName: "blockquote-block",
            active: false,
        },
        codeBlock: {
            formatName: "code-block",
            active: false,
        },
        spoiler: {

            active: false,
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
            showMenu: false,
            range: this.constructor.initialRange,
        };
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
        this.quill.off(Emitter.events.EDITOR_CHANGE, this.handleEditorChange);
        Events.removeResizeListener(this.resizeListener);
    }

    /**
     * Handle changes from the editor.
     *
     * @param {string} type - The event type. See {quill/core/emitter}
     * @param {RangeStatic} range - The new range.
     * @param {RangeStatic} oldRange - The old range.
     * @param {Sources} source - The source of the change.
     */
    handleEditorChange = (type, range, oldRange, source) => {
        if (range && range.length === 0) {
            this.setState({
                range,
            });
        }

        if (source !== Quill.sources.SILENT) {
            this.setState({
                showMenu: false,
            });
        }
    };

    getPilcroStyles() {
        const bounds = this.quill.getBounds(this.state.range);

        // This is the pixel offset from the top needed to make things align correctly.
        const offset = 9 + 2;

        return {
            top: (bounds.top + bounds.bottom) / 2 - offset,
        };
    }

    getToolbarStyles() {
        const hiddenStyles = {
            visibility: "hidden",
            position: "absolute",
            zIndex: -1,
        };

        return this.state.showMenu ? {} : hiddenStyles;
    }

    /**
     * Click handler for the Pilcro
     *
     * @param {React.MouseEvent} event
     */
    pilcroClickHandler = (event) => {
        event.preventDefault();
        this.setState({
            showMenu: !this.state.showMenu,
        });
    };

    render() {
        const toolbarStyles = this.getToolbarStyles();
        const pilcroStyles = this.getPilcroStyles();

        return <div style={pilcroStyles} className="richEditor-menu richEditorParagraphMenu">
            <button
                className="richEditor-button richEditorParagraphMenu-handle"
                type="button"
                aria-haspopup="menu"
                aria-expanded="false"
                aria-controls="tempId-paragraphLevelMenu-toggle"
                onClick={this.pilcroClickHandler}
            >
                <PilcroIcon/>
            </button>
            <div style={toolbarStyles} ref={(ref) => this.toolbarNode = ref}>
                <EditorToolbar quill={this.quill} menuItems={this.menuItems} isHidden={!this.state.showMenu}/>
            </div>
        </div>;
    }
}
