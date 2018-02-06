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
import { Range } from "quill/core/selection";
import Keyboard from "quill/modules/keyboard";
import LinkBlot from "quill/formats/link";
import FloatingToolbar from "./FloatingToolbar";
import { t } from "@core/utility";

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
            formatter: () => {
                this.setState({
                    previousRange: this.quill.getSelection(),
                });
                this.focusLinkInput();
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
            showLink: false,
            value: "",
            previousRange: {},
        };

        this.handleEditorChange = this.handleEditorChange.bind(this);
    }

    /**
     * Mount quill listeners.
     */
    componentDidMount() {
        this.quill.on(Emitter.events.EDITOR_CHANGE, this.handleEditorChange);
    }

    /**
     * Be sure to remove the listeners when the component unmounts.
     */
    componentWillUnmount() {
        this.quill.off(Quill.events.EDITOR_CHANGE, this.handleEditorChange);
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
            const [link, offset] = this.quill.scroll.descendant(LinkBlot, range.index);
            if (link) {
                const linkRange = new Range(range.index - offset, link.length())
                const href = LinkBlot.formats(link.domNode);
                this.setState({
                    value: href,
                    previousSelection: linkRange,
                });
                this.focusLinkInput();
            } else {
                this.clearLinkInput();
            }
        } else if (!this.state.ignoreSelectionReset) {
            this.clearLinkInput();
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
            this.setState({
                showLink: false,
            });
        }

        if (Keyboard.match(event.nativeEvent, "escape")) {
            this.setState({
                showLink: false,
            });
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
        this.quill.setSelection(this.state.previousRange, Emitter.sources.USER);
        this.setState({
            showLink: false,
        });
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
