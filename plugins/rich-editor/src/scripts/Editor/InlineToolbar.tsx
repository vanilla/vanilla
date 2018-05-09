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
import { t, isAllowedUrl } from "@core/application";
import SelectionPositionToolbar from "./SelectionPositionToolbarContainer";
import Toolbar from "./Generic/Toolbar";
import { withEditor, IEditorContextProps } from "./ContextProvider";
import { IMenuItemData } from "./Generic/MenuItem";
import CodeBlot from "../Quill/Blots/Inline/CodeBlot";
import { rangeContainsBlot, CLOSE_FLYOUT_EVENT, disableAllBlotsInRange } from "../Quill/utility";
import CodeBlockBlot from "../Quill/Blots/Blocks/CodeBlockBlot";

interface IProps extends IEditorContextProps {}

interface IState {
    cachedRange: RangeStatic | null;
    linkValue: string;
    showFormatMenu: boolean;
    showLinkMenu: boolean;
}

export class InlineToolbar extends React.Component<IProps, IState> {
    private quill: Quill;
    private linkInput: HTMLElement;
    private ignoreSelectionChange = false;
    private selfRef: React.RefObject<any> = React.createRef();

    private menuItems = {
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
            formatName: "code-inline",
            active: false,
            formatter: this.codeFormatter.bind(this),
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
            linkValue: "",
            cachedRange: {
                index: 0,
                length: 0,
            },
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
        const restrictedFormats = this.restrictedFormats;

        return (
            <div ref={this.selfRef}>
                <SelectionPositionToolbar selection={this.state.cachedRange} isVisible={this.state.showFormatMenu}>
                    {alertMessage}
                    <Toolbar
                        restrictedFormats={restrictedFormats}
                        menuItems={this.menuItems}
                        onBlur={this.toolbarBlurHandler}
                    />
                </SelectionPositionToolbar>
                <SelectionPositionToolbar selection={this.state.cachedRange} isVisible={this.state.showLinkMenu}>
                    <div className="richEditor-menu FlyoutMenu insertLink" role="dialog" aria-label={t("Insert Url")}>
                        <input
                            value={this.state.linkValue}
                            onChange={this.onLinkInputChange}
                            ref={ref => (this.linkInput = ref as HTMLElement)}
                            onKeyDown={this.onLinkKeyDown}
                            className="InputBox insertLink-input"
                            placeholder={t("Paste or type a link…")}
                        />
                        <button type="button" onClick={this.onCloseClick} className="Close richEditor-close">
                            <span className="Close-x" aria-hidden="true">
                                {t("×")}
                            </span>
                            <span className="sr-only">{t("Close")}</span>
                        </button>
                    </div>
                </SelectionPositionToolbar>
            </div>
        );
    }

    /**
     * Mount quill listeners.
     */
    public componentDidMount() {
        this.quill.on(Quill.events.EDITOR_CHANGE, this.handleEditorChange);
        document.addEventListener("keydown", this.escFunction, false);
        document.addEventListener(CLOSE_FLYOUT_EVENT, this.reset);

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
        document.removeEventListener(CLOSE_FLYOUT_EVENT, this.reset);
    }

    /**
     * Get the restricted formats for the format toolbar.
     *
     * Should exclude everything else if inline code is selected.
     */
    private get restrictedFormats(): string[] | null {
        if (this.inCodeInline) {
            return Object.keys(this.menuItems).filter(key => key !== "code");
        } else {
            return null;
        }
    }

    /**
     * Determine if the current selection contains an inline code format.
     */
    private get inCodeInline(): boolean {
        const selection = this.quill.getSelection();
        if (!selection) {
            return false;
        }

        return rangeContainsBlot(this.quill, selection, CodeBlot);
    }

    /**
     * Determine if the current selection contains a code block.
     */
    private get inCodeBlock(): boolean {
        const selection = this.quill.getSelection();
        if (!selection) {
            return false;
        }
        return rangeContainsBlot(this.quill, selection, CodeBlockBlot);
    }

    /**
     * Handle create-link keyboard shortcut.
     */
    private commandKHandler = () => {
        const { cachedRange, showFormatMenu, showLinkMenu } = this.state;
        if (cachedRange && cachedRange.length && !showLinkMenu && !this.inCodeInline && !this.inCodeBlock) {
            if (rangeContainsBlot(this.quill, cachedRange, LinkBlot)) {
                disableAllBlotsInRange(this.quill, cachedRange, LinkBlot);
                this.clearLinkInput();
                this.quill.update(Quill.sources.USER);
            } else {
                const currentText = this.quill.getText(cachedRange.index, cachedRange.length);
                this.focusLinkInput();

                if (isAllowedUrl(currentText)) {
                    this.setState({
                        linkValue: currentText,
                    });
                }
            }
        }
    };

    /**
     * This is a no-op for now.
     */
    private toolbarBlurHandler = (event: React.FocusEvent<any>) => {
        return;
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
                const range = this.quill.getSelection(true);
                this.quill.setSelection(range.length + range.index, 0, Emitter.sources.USER);
            }
        }
    };

    private reset = () => {
        this.setState({
            linkValue: "",
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
                showFormatMenu: !this.inCodeBlock,
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
    private linkFormatter(menuItemData: IMenuItemData) {
        if (menuItemData.active) {
            const range = this.quill.getSelection();
            disableAllBlotsInRange(this.quill, range, LinkBlot);
            this.clearLinkInput();
        } else {
            this.focusLinkInput();
        }
    }

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
                this.linkInput.focus();
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
            linkValue: "",
            showLinkMenu: false,
        });
    };

    /**
     * Handle key-presses for the link toolbar.
     */
    private onLinkKeyDown = (event: React.KeyboardEvent<any>) => {
        if (Keyboard.match(event.nativeEvent, "enter")) {
            event.preventDefault();
            this.quill.format("link", this.state.linkValue, Emitter.sources.USER);
            this.clearLinkInput();
        }
    };

    /**
     * Handle changes to the the close menu's input.
     */
    private onLinkInputChange = (event: React.ChangeEvent<any>) => {
        this.setState({ linkValue: event.target.value });
    };
}

export default withEditor<IProps>(InlineToolbar);
