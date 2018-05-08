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
    showLink: boolean;
    ignoreSelectionReset: boolean;
    previousRange: RangeStatic;
    value: string;
    isUrlInputVisible: boolean;
    isMenuVisible: boolean;
}

export class InlineToolbar extends React.Component<IProps, IState> {
    private quill: Quill;
    private linkInput: HTMLElement;

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
            ignoreSelectionReset: false,
            previousRange: {
                index: 0,
                length: 0,
            },
            isUrlInputVisible: false,
            isMenuVisible: false,
        };
    }

    public render() {
        const alertMessage = this.state.showLink ? null : (
            <span aria-live="assertive" role="alert" className="sr-only">
                {t("Inline Menu Available")}
            </span>
        );
        const restrictedFormats = this.restrictedFormats;
        let formatMenuVisibility = this.state.showLink ? "hidden" : "ignore";
        let linkMenuVisibility = this.state.showLink ? "visible" : "hidden";

        if (this.inCodeBlock) {
            formatMenuVisibility = "hidden";
            linkMenuVisibility = "hidden";
        }

        return (
            <div>
                <SelectionPositionToolbar
                    setVisibility={this.setVisibilityOfMenu}
                    forceVisibility={formatMenuVisibility}
                >
                    {alertMessage}
                    <Toolbar
                        restrictedFormats={restrictedFormats}
                        menuItems={this.menuItems}
                        onBlur={this.toolbarBlurHandler}
                    />
                </SelectionPositionToolbar>
                <SelectionPositionToolbar
                    setVisibility={this.setVisibilityOfUrlInput}
                    forceVisibility={linkMenuVisibility}
                >
                    <div className="richEditor-menu FlyoutMenu insertLink" role="dialog" aria-label={t("Insert Url")}>
                        <input
                            value={this.state.value}
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
        this.quill.on(Quill.events.SELECTION_CHANGE, this.handleSelectionChange);
        document.addEventListener("keydown", this.escFunction, false);
        document.addEventListener(CLOSE_FLYOUT_EVENT, this.clearLinkInput);

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
        this.quill.off(Quill.events.SELECTION_CHANGE, this.handleSelectionChange);
        document.removeEventListener("keydown", this.escFunction, false);
        document.removeEventListener(CLOSE_FLYOUT_EVENT, this.clearLinkInput);
    }

    private get restrictedFormats(): string[] | null {
        const selection = this.quill.getSelection();
        if (!selection) {
            return null;
        }

        const formats = this.quill.getFormat(selection);
        if (rangeContainsBlot(this.quill, selection, CodeBlot)) {
            return Object.keys(this.menuItems).filter(key => key !== "code");
        } else {
            return null;
        }
    }

    private get inCodeBlock(): boolean {
        const selection = this.quill.getSelection();
        if (!selection) {
            return false;
        }
        return rangeContainsBlot(this.quill, selection, CodeBlockBlot);
    }

    private commandKHandler = () => {
        const range = this.quill.getSelection();
        if (range.length) {
            if (rangeContainsBlot(this.quill, range, LinkBlot)) {
                disableAllBlotsInRange(this.quill, range, LinkBlot);
                this.clearLinkInput();
                this.quill.update(Quill.sources.USER);
            } else {
                const currentText = this.quill.getText(range.index, range.length);
                this.focusLinkInput();

                if (isAllowedUrl(currentText)) {
                    this.setState({
                        value: currentText,
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
        if (event.keyCode === 27 && (this.state.isMenuVisible || this.state.isUrlInputVisible)) {
            this.setState({
                value: "",
                showLink: false,
            });
            const range = this.quill.getSelection(true);
            this.quill.setSelection(range.length + range.index, 0, Emitter.sources.USER);
        }
    };

    /**
     * Handle changes from the editor.
     */
    private handleSelectionChange = (range: RangeStatic, oldRange: RangeStatic, source: Sources) => {
        if (range && range.length > 0 && source === Emitter.sources.USER) {
            this.clearLinkInput();
        } else if (!this.state.ignoreSelectionReset) {
            this.clearLinkInput();
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
     * Apply focus to the link input.
     *
     * We need to temporarily stop ignore selection changes for the link menu (it will lose selection).
     */
    private focusLinkInput() {
        this.setState(
            {
                showLink: true,
                ignoreSelectionReset: true,
                previousRange: this.quill.getSelection(),
            },
            () => {
                this.linkInput.focus();
                setTimeout(() => {
                    this.setState({
                        ignoreSelectionReset: false,
                    });
                }, 100);
            },
        );
    }

    /**
     * Clear the link menu's input content and hide the link menu..
     */
    private clearLinkInput = () => {
        this.setState({
            value: "",
            showLink: false,
        });
    };

    /**
     * Handle key-presses for the link toolbar.
     */
    private onLinkKeyDown = (event: React.KeyboardEvent<any>) => {
        if (Keyboard.match(event.nativeEvent, "enter")) {
            event.preventDefault();
            const value = (event.target as HTMLInputElement).value || "";
            this.quill.format("link", value, Emitter.sources.USER);
            this.quill.setSelection(this.state.previousRange, Emitter.sources.USER);
            this.clearLinkInput();
        }

        if (Keyboard.match(event.nativeEvent, "escape")) {
            this.clearLinkInput();
            this.quill.setSelection(this.state.previousRange, Emitter.sources.USER);
        }
    };

    /**
     * Handle clicks on the link menu's close button.
     */
    private onCloseClick = (event: React.MouseEvent<any>) => {
        event.preventDefault();
        this.clearLinkInput();
        this.quill.setSelection(this.state.previousRange, Emitter.sources.USER);
    };

    /**
     * Handle changes to the the close menu's input.
     */
    private onLinkInputChange = (event: React.ChangeEvent<any>) => {
        this.setState({ value: event.target.value });
    };

    /**
     * Set visibility of url input
     */
    private setVisibilityOfUrlInput = (isVisible: boolean) => {
        this.setState({
            isUrlInputVisible: isVisible,
        });
    };

    /**
     * Set visibility of url input
     */
    private setVisibilityOfMenu = (isVisible: boolean) => {
        this.setState({
            isMenuVisible: isVisible,
        });
    };
}

export default withEditor<IProps>(InlineToolbar);
