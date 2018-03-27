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
import { pilcrow as PilcrowIcon } from "./Icons";
import { closeEditorFlyouts, CLOSE_FLYOUT_EVENT } from "../quill-utilities";
import UniqueID from "react-html-id";

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
     * @property {RangeStatic} range - The current quill selected text range.
     * @property {number} showMenu - Whether or not to display the Paragraph toolbar.
     */
    state;

    /** @type {HTMLElement} */
    toolbarNode;

    /** @type {HTMLElement} */
    nub;

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
            formatName: "blockquote-line",
            active: false,
        },
        codeBlock: {
            formatName: "code-block",
            active: false,
        },
        spoiler: {
            formatName: "spoiler-line",
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
        UniqueID.enableUniqueIds(this);
        this.ID = this.nextUniqueId();
        this.componentID = "paragraphMenu-component-" + this.ID;
        this.menuID = "paragraphMenu-menu-" + this.ID;
        this.buttonID = "paragraphMenu-button-" + this.ID;
        this.state = {
            showPilcrow: true,
            showMenu: false,
            range: this.constructor.initialRange,
        };
    }

    /**
     * Mount quill listeners.
     */
    componentDidMount() {
        this.quill.on(Emitter.events.EDITOR_CHANGE, this.handleEditorChange);
        document.addEventListener(CLOSE_FLYOUT_EVENT, this.closeMenu);
    }

    /**
     * Be sure to remove the listeners when the component unmounts.
     */
    componentWillUnmount() {
        this.quill.off(Emitter.events.EDITOR_CHANGE, this.handleEditorChange);
        document.removeEventListener(CLOSE_FLYOUT_EVENT, this.closeMenu);
    }

    /**
     * Close the menu.
     *
     * @param {Event} event -
     */
    closeMenu = (event) => {
        if (event.detail && event.detail.firingKey === this.constructor.name) {
            return;
        }

        this.setState({
            showMenu: false,
        });
    };

    /**
     * Handle changes from the editor.
     *
     * @param {string} type - The event type. See {quill/core/emitter}
     * @param {RangeStatic} range - The new range.
     * @param {RangeStatic} oldRange - The old range.
     * @param {Sources} source - The source of the change.
     */
    handleEditorChange = (type, range, oldRange, source) => {
        if (range) {
            if (typeof range.index !== "number") {
                range = this.quill.getSelection();
            }

            if (range != null) {
                this.setState({
                    range,
                });
            }
        }

        if (source !== Quill.sources.SILENT) {
            this.setState({
                showMenu: false,
            });
        }

        let numLines = 0;

        if (range) {
            numLines = this.quill.getLines(range.index || 0, range.length || 0);
        }

        if (numLines.length <= 1 && !this.state.showPilcrow) {
            this.setState({
                showPilcrow: true,
            });
        } else if (numLines.length > 1) {
            this.setState({
                showPilcrow: false,
            });
        }
    };

    getPilcrowStyles() {
        const bounds = this.quill.getBounds(this.state.range);

        // This is the pixel offset from the top needed to make things align correctly.
        const offset = 9 + 2;

        return {
            top: (bounds.top + bounds.bottom) / 2 - offset,
        };
    }

    getToolbarClasses() {
        const bounds = this.quill.getBounds(this.state.range);
        let classes = "richEditor-toolbarContainer richEditor-paragraphToolbarContainer";

        if (bounds.top > 30) {
            classes += " isUp";
        } else {
            classes += " isDown";
        }

        return classes;
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
     * Click handler for the Pilcrow
     *
     * @param {React.MouseEvent} event - The event from the click handler.
     */
    pilcrowClickHandler = (event) => {
        event.preventDefault();
        this.setState({
            showMenu: !this.state.showMenu,
        });
        closeEditorFlyouts(this.constructor.name);
    };

    /**
     * Close if we lose focus on the component
     * @param {React.FocusEvent} event - A synthetic event.
     */
    checkForExternalFocus = (event) => {
        setImmediate(() => {
            const activeElement = document.activeElement;
            const paragraphMenu = document.getElementById(this.componentID);
            if (activeElement.id !== paragraphMenu && !paragraphMenu.contains(activeElement)) {
                this.closeMenu(event);
            }
        });
    };

    render() {
        let pilcrowClasses = "richEditor-button richEditorParagraphMenu-handle";
        if (!this.state.showPilcrow) {
            pilcrowClasses += " isHidden";
        }

        return <div id={this.componentID} style={this.getPilcrowStyles()} className="richEditor-menu richEditorParagraphMenu">
            <button
                type="button"
                id={this.buttonID}
                aria-controls={this.menuID}
                aria-expanded={this.state.showMenu}
                disabled={!this.state.showPilcrow}
                className={pilcrowClasses}
                aria-haspopup="true"
                onClick={this.pilcrowClickHandler}
            >
                <PilcrowIcon/>
            </button>
            <div id={this.menuID} className={this.getToolbarClasses()} style={this.getToolbarStyles()} ref={(ref) => this.toolbarNode = ref} role="menu">
                <EditorToolbar quill={this.quill} menuItems={this.menuItems} isHidden={!this.state.showMenu} checkForExternalFocus={this.checkForExternalFocus}/>
                <div role="presentation" className="richEditor-nubPosition">
                    <div className="richEditor-nub"/>
                </div>
            </div>
        </div>;
    }
}
