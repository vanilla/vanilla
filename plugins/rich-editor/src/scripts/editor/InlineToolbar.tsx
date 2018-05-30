/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import Quill, { RangeStatic, Sources } from "quill/core";
import Emitter from "quill/core/emitter";
import Keyboard from "quill/modules/keyboard";
import LinkBlot from "quill/formats/link";
import { t, isAllowedUrl } from "@dashboard/application";
import SelectionPositionToolbar from "./SelectionPositionToolbarContainer";
import Toolbar from "./generic/Toolbar";
import { withEditor, IEditorContextProps } from "./ContextProvider";
import { IMenuItemData } from "./generic/MenuItem";
import CodeBlot from "../quill/blots/inline/CodeBlot";
import { rangeContainsBlot, disableAllBlotsInRange } from "../quill/utility";
import CodeBlockBlot from "../quill/blots/blocks/CodeBlockBlot";
import InlineToolbarItems from "./InlineToolbarItems";
import InlineToolbarLinkInput from "./InlineToolbarLinkInput";
import { watchFocusInDomTree } from "@dashboard/dom";

interface IProps extends IEditorContextProps {}

interface IState {
    cachedRange: RangeStatic | null;
    inputValue: string;
    showFormatMenu: boolean;
    showLinkMenu: boolean;
    hasFocus: boolean;
    quillHasFocus: boolean;
}

export class InlineToolbar extends React.Component<IProps, IState> {
    private quill: Quill;
    private linkInput: React.RefObject<HTMLInputElement> = React.createRef();
    private selfRef: React.RefObject<HTMLDivElement> = React.createRef();
    private ignoreSelectionChange = false;

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;

        this.state = {
            inputValue: "",
            cachedRange: {
                index: 0,
                length: 0,
            },
            hasFocus: false,
            quillHasFocus: false,
            showFormatMenu: false,
            showLinkMenu: false,
        };
    }

    public render() {
        const alertMessage = this.state.showFormatMenu ? (
            <span aria-live="assertive" role="alert" className="sr-only">
                {t("Inline Menu Available")}
            </span>
        ) : null;

        const hasFocus = this.state.hasFocus || this.state.quillHasFocus;

        return (
            <div ref={this.selfRef}>
                <SelectionPositionToolbar
                    selection={this.state.cachedRange}
                    isVisible={this.state.showFormatMenu && hasFocus}
                >
                    {alertMessage}
                    <InlineToolbarItems currentSelection={this.state.cachedRange} linkFormatter={this.linkFormatter} />
                </SelectionPositionToolbar>
                <SelectionPositionToolbar
                    selection={this.state.cachedRange}
                    isVisible={this.state.showLinkMenu && hasFocus}
                >
                    <InlineToolbarLinkInput
                        inputRef={this.linkInput}
                        inputValue={this.state.inputValue}
                        onInputChange={this.onInputChange}
                        onInputKeyDown={this.onInputKeyDown}
                        onCloseClick={this.onCloseClick}
                    />
                </SelectionPositionToolbar>
            </div>
        );
    }

    /**
     * Mount quill listeners.
     */
    public componentDidMount() {
        document.addEventListener("keydown", this.escFunction, false);
        this.quill.on(Quill.events.EDITOR_CHANGE, this.handleEditorChange);
        watchFocusInDomTree(this.selfRef.current!, this.handleFocusChange);
        watchFocusInDomTree(this.quill.root!, this.handleQuillFocusChange);

        // Add a key binding for the link popup.
        const keyboard: Keyboard = this.quill.getModule("keyboard");
        keyboard.addBinding(
            {
                key: "k",
                metaKey: true,
            },
            {},
            this.commandKHandler,
        );
    }

    /**
     * Be sure to remove the listeners when the component unmounts.
     */
    public componentWillUnmount() {
        this.quill.off(Quill.events.EDITOR_CHANGE, this.handleEditorChange);
        document.removeEventListener("keydown", this.escFunction, false);
    }

    private handleFocusChange = hasFocus => {
        this.setState({ hasFocus });
    };

    private handleQuillFocusChange = hasFocus => {
        this.setState({ quillHasFocus: hasFocus });
    };

    /**
     * Handle create-link keyboard shortcut.
     */
    private commandKHandler = () => {
        const { cachedRange, showFormatMenu, showLinkMenu } = this.state;
        if (
            cachedRange &&
            cachedRange.length &&
            !showLinkMenu &&
            !rangeContainsBlot(this.quill, CodeBlot) &&
            !rangeContainsBlot(this.quill, CodeBlockBlot)
        ) {
            if (rangeContainsBlot(this.quill, LinkBlot, cachedRange)) {
                disableAllBlotsInRange(this.quill, LinkBlot, cachedRange);
                this.clearLinkInput();
                this.quill.update(Quill.sources.USER);
            } else {
                const currentText = this.quill.getText(cachedRange.index, cachedRange.length);
                this.focusLinkInput();

                if (isAllowedUrl(currentText)) {
                    this.setState({
                        inputValue: currentText,
                    });
                }
            }
        }
    };

    /**
     * Close the menu.
     */
    private escFunction = (event: KeyboardEvent) => {
        if (event.keyCode === 27) {
            if (this.state.showLinkMenu) {
                event.preventDefault();
                this.clearLinkInput();
            } else if (this.state.showFormatMenu) {
                event.preventDefault();
                this.reset();
            }
        }
    };

    private reset = () => {
        if (this.state.cachedRange) {
            const { activeElement } = document;
            this.quill.setSelection(
                this.state.cachedRange.length + this.state.cachedRange.index,
                0,
                Emitter.sources.USER,
            );
            (activeElement as any).focus();
        }

        this.setState({
            inputValue: "",
            showLinkMenu: false,
            showFormatMenu: false,
            cachedRange: null,
        });
    };

    /**
     * Handle clicks on the link menu's close button.
     */
    private onCloseClick = (event: React.MouseEvent<any>) => {
        event.preventDefault();
        this.clearLinkInput();
    };

    /**
     * Handle changes from the editor.
     */
    private handleEditorChange = (type: string, range: RangeStatic, oldRange: RangeStatic, source: Sources) => {
        const isTextOrSelectionChange = type === Quill.events.SELECTION_CHANGE || type === Quill.events.TEXT_CHANGE;
        if (source === Quill.sources.SILENT || !isTextOrSelectionChange || this.ignoreSelectionChange) {
            return;
        }

        if (range && range.length > 0 && source === Emitter.sources.USER) {
            this.setState({
                cachedRange: range,
                showFormatMenu: !rangeContainsBlot(this.quill, CodeBlockBlot),
            });
        } else {
            this.setState({
                cachedRange: null,
                showFormatMenu: false,
                showLinkMenu: false,
            });
        }
    };

    /**
     * Special formatting for the link blot.
     *
     * @param menuItemData - The current state of the menu item.
     */
    private linkFormatter = (menuItemData: IMenuItemData) => {
        if (menuItemData.active) {
            disableAllBlotsInRange(this.quill, LinkBlot);
            this.clearLinkInput();
        } else {
            this.focusLinkInput();
        }
    };

    /**
     * Be sure to strip out all other formats before formatting as code.
     */
    private codeFormatter(menuItemData: IMenuItemData) {
        if (!this.state.cachedRange) {
            return;
        }
        this.quill.removeFormat(this.state.cachedRange.index, this.state.cachedRange.length, Quill.sources.API);
        this.quill.formatText(
            this.state.cachedRange.index,
            this.state.cachedRange.length,
            "code-inline",
            !menuItemData.active,
            Quill.sources.USER,
        );
    }

    /**
     * Apply focus to the link input.
     *
     * We need to temporarily stop ignore selection changes for the link menu (it will lose selection).
     */
    private focusLinkInput() {
        this.ignoreSelectionChange = true;
        this.setState(
            {
                showLinkMenu: true,
                showFormatMenu: false,
            },
            () => {
                this.linkInput.current && this.linkInput.current.focus();
                setTimeout(() => {
                    this.ignoreSelectionChange = false;
                }, 100);
            },
        );
    }

    /**
     * Clear the link menu's input content and hide the link menu.
     */
    private clearLinkInput = () => {
        this.state.cachedRange && this.quill.setSelection(this.state.cachedRange, Emitter.sources.USER);

        this.setState({
            inputValue: "",
            showLinkMenu: false,
        });
    };

    /**
     * Handle key-presses for the link toolbar.
     */
    private onInputKeyDown = (event: React.KeyboardEvent<any>) => {
        if (Keyboard.match(event.nativeEvent, "enter")) {
            event.preventDefault();
            this.quill.format("link", this.state.inputValue, Emitter.sources.USER);
            this.clearLinkInput();
        }
    };

    /**
     * Handle changes to the the close menu's input.
     */
    private onInputChange = (event: React.ChangeEvent<any>) => {
        this.setState({ inputValue: event.target.value });
    };
}

export default withEditor<IProps>(InlineToolbar);
