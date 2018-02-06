/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import Quill from "quill/quill";
import EditorToolbar from "./EditorToolbar";
import Emitter from "quill/core/emitter";
import Keyboard from "quill/modules/keyboard";
import LinkBlot from "quill/formats/link";
import FloatingToolbar from "./FloatingToolbar";
import { t } from "@core/utility";
import * as quillUtilities from "../quill-utilities";

export default class InlineEditorToolbar extends React.Component {
    static propTypes = {
        quill: PropTypes.instanceOf(Quill).isRequired,
    };

    /** @type {Quill} */
    quill;

    /**
     * @type {Object}
     * @property {boolean} showLink
     * @property {boolean} ignoreSelectionReset
     */
    state;

    /** @type {HTMLElement} */
    linkInput;

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
            formatter: this.linkFormatter.bind(this),
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
            showLink: false,
            value: "",
            previousRange: {},
        };
    }

    /**
     * Mount quill listeners.
     */
    componentDidMount() {
        this.quill.on(Emitter.events.EDITOR_CHANGE, this.handleEditorChange);
        this.quill.root.addEventListener("LinkShortcut", () => {
            if (this.quill.getSelection().length > 0) {
                this.focusLinkInput();
            }
        });
    }

    /**
     * Be sure to remove the listeners when the component unmounts.
     */
    componentWillUnmount() {
        this.quill.off(Quill.events.EDITOR_CHANGE, this.handleEditorChange);
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
        if (type !== Emitter.events.SELECTION_CHANGE) {
            return;
        }

        if (range && range.length > 0 && source === Emitter.sources.USER) {
            this.clearLinkInput();
        } else if (!this.state.ignoreSelectionReset) {
            this.clearLinkInput();
        }
    };


    /**
     * Format (or unformat) link blots in a given area. Will fully unformat a link even if the link is not entirely
     * inside of the current selection.
     *
     * @param {MenuItemData} menuItemData - The current state of the menu item.
     */
    linkFormatter(menuItemData) {
        if (menuItemData.active) {
            const range = this.quill.getSelection();

            /** @type {Blot[]} */
            const currentLinks = this.quill.scroll.descendants(LinkBlot, range.index, range.length);
            const firstLink = currentLinks[0];
            const lastLink = currentLinks[currentLinks.length - 1];

            const startRange = firstLink && {
                index: firstLink.offset(this.quill.scroll),
                length: firstLink.length(),
            };

            const endRange = lastLink && {
                index: lastLink.offset(this.quill.scroll),
                length: lastLink.length(),
            };
            const finalRange = quillUtilities.expandRange(range, startRange, endRange);

            this.quill.formatText(finalRange.index, finalRange.length, 'link', false, Emitter.sources.USER);
            this.clearLinkInput();
        } else {
            this.focusLinkInput();
        }
    }

    /**
     * Apply focus to the link input.
     *
     * We need to temporarily stop ignore selection changes for the link menu (it will lose selection).
     */
    focusLinkInput() {
        this.setState({
            showLink: true,
            ignoreSelectionReset: true,
            previousRange: this.quill.getSelection(),
        }, () => {
            this.linkInput.focus();
            setTimeout(() => {
                this.setState({
                    ignoreSelectionReset: false,
                });
            }, 100);
        });
    }

    /**
     * Clear the link menu's input content and hide the link menu..
     */
    clearLinkInput() {
        this.setState({
            value: "",
            showLink: false,
        });
    }

    /**
     * Handle key-presses for the link toolbar.
     *
     * @param {React.KeyboardEvent} event - The key-press event.
     */
    onLinkKeyDown = (event) => {
        if (Keyboard.match(event.nativeEvent, "enter")) {
            event.preventDefault();
            const value = event.target.value || "";
            this.quill.format('link', value, Emitter.sources.USER);
            this.clearLinkInput();
        }

        if (Keyboard.match(event.nativeEvent, "escape")) {
            this.clearLinkInput();
            this.quill.setSelection(this.state.previousRange, Emitter.sources.USER);
        }
    };

    /**
     * Handle clicks on the link menu's close button.
     *
     * @param {React.MouseEvent} event - The click event.
     */
    onCloseClick = (event) => {
        event.preventDefault();
        this.clearLinkInput();
        this.quill.setSelection(this.state.previousRange, Emitter.sources.USER);
    };

    /**
     * Handle changes to the the close menu's input.
     *
     * @param {React.SyntheticEvent} event -
     */
    onLinkInputChange = (event) => {
        this.setState({value: event.target.value});
    };

    /**
     * @inheritDoc
     */
    render() {
        return <div>
            <FloatingToolbar quill={this.quill} forceVisibility={this.state.showLink ? "hidden" : "ignore"}>
                <EditorToolbar quill={this.quill} menuItems={this.menuItems}/>
            </FloatingToolbar>
            <FloatingToolbar quill={this.quill} forceVisibility={this.state.showLink ? "visible" : "hidden"}>
                <div className="richEditor-menu FlyoutMenu insertLink" role="dialog" aria-label={t("Insert Url")}>
                    <input
                        value={this.state.value}
                        onChange={this.onLinkInputChange}
                        ref={(ref) => this.linkInput = ref}
                        onKeyDown={this.onLinkKeyDown}
                        className="InputBox insertLink-input"
                        placeholder={t("Paste or type a link…")}
                    />
                    <a href="#"
                        aria-label={t("Close")}
                        className="Close richEditor-close"
                        role="button"
                        onClick={this.onCloseClick}>
                        <span>×</span>
                    </a>
                </div>
            </FloatingToolbar>
        </div>;
    }
}
