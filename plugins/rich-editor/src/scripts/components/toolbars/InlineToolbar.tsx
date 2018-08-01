/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import Quill from "quill/core";
import Emitter from "quill/core/emitter";
import Keyboard from "quill/modules/keyboard";
import LinkBlot from "quill/formats/link";
import { t, isAllowedUrl } from "@dashboard/application";
import ToolbarContainer from "./pieces/ToolbarContainer";
import { withEditor, IWithEditorProps } from "@rich-editor/components/context";
import InlineToolbarLinkInput from "./pieces/InlineToolbarLinkInput";
import { watchFocusInDomTree } from "@dashboard/dom";
import { rangeContainsBlot } from "@rich-editor/quill/utility";
import CodeBlot from "@rich-editor/quill/blots/inline/CodeBlot";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import Formatter from "@rich-editor/quill/Formatter";
import InlineToolbarMenuItems from "@rich-editor/components/toolbars/pieces/InlineToolbarMenuItems";

interface IProps extends IWithEditorProps {}

interface IState {
    inputValue: string;
    isLinkMenuOpen: boolean;
    menuHasFocus: boolean;
    quillHasFocus: boolean;
}

/**
 * This __cannot__ be a pure component because it needs to re-render when quill emits, even if the selection is the same.
 */
export class InlineToolbar extends React.Component<IProps, IState> {
    private quill: Quill;
    private formatter: Formatter;
    private linkInput: React.RefObject<HTMLInputElement> = React.createRef();
    private selfRef: React.RefObject<HTMLDivElement> = React.createRef();

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;
        this.formatter = new Formatter(this.quill);

        this.state = {
            inputValue: "",
            isLinkMenuOpen: false,
            menuHasFocus: false,
            quillHasFocus: false,
        };
    }

    /**
     * Reset the link menu state when the selection changes.
     */
    public componentDidUpdate(prevProps: IProps) {
        const selection = this.props.instanceState.lastGoodSelection;
        const prevSelection = prevProps.instanceState.lastGoodSelection;
        if (prevSelection.index !== selection.index || prevSelection.length !== selection.length) {
            this.setState({ isLinkMenuOpen: false });
        }
    }

    public render() {
        const { activeFormats, instanceState } = this.props;
        const alertMessage = this.isFormatMenuVisible ? (
            <span aria-live="assertive" role="alert" className="sr-only">
                {t("Inline Menu Available")}
            </span>
        ) : null;

        return (
            <div ref={this.selfRef}>
                <ToolbarContainer selection={instanceState.lastGoodSelection} isVisible={this.isFormatMenuVisible}>
                    {alertMessage}
                    <InlineToolbarMenuItems
                        formatter={this.formatter}
                        onLinkClick={this.toggleLinkMenu}
                        activeFormats={activeFormats}
                        lastGoodSelection={instanceState.lastGoodSelection}
                    />
                </ToolbarContainer>
                <ToolbarContainer selection={instanceState.lastGoodSelection} isVisible={this.isLinkMenuVisible}>
                    <InlineToolbarLinkInput
                        inputRef={this.linkInput}
                        inputValue={this.state.inputValue}
                        onInputChange={this.onInputChange}
                        onInputKeyDown={this.onInputKeyDown}
                        onCloseClick={this.onCloseClick}
                    />
                </ToolbarContainer>
            </div>
        );
    }

    /**
     * Determine visibility of the link menu.
     */
    private get isLinkMenuVisible(): boolean {
        const inCodeBlock = rangeContainsBlot(this.quill, CodeBlockBlot, this.props.instanceState.lastGoodSelection);
        return this.state.isLinkMenuOpen && this.hasFocus && this.isOneLineOrLess && !inCodeBlock;
    }

    /**
     * Determine visibility of the formatting menu.
     */
    private get isFormatMenuVisible(): boolean {
        const selectionHasLength = this.props.instanceState.lastGoodSelection.length > 0;
        const inCodeBlock = rangeContainsBlot(this.quill, CodeBlockBlot, this.props.instanceState.lastGoodSelection);
        return !this.isLinkMenuVisible && this.hasFocus && selectionHasLength && this.isOneLineOrLess && !inCodeBlock;
    }

    /**
     * Determine if our selection spreads over multiple lines or not.
     */
    private get isOneLineOrLess(): boolean {
        const { lastGoodSelection } = this.props.instanceState;
        const numLines = this.quill.getLines(lastGoodSelection.index || 0, lastGoodSelection.length || 0).length;
        return numLines <= 1;
    }

    /**
     * Determine if the inline menu or the quill content editable has focus.
     */
    private get hasFocus() {
        return this.state.menuHasFocus || this.quill.hasFocus();
    }

    /**
     * Mount quill listeners.
     */
    public componentDidMount() {
        document.addEventListener("keydown", this.escFunction, false);
        watchFocusInDomTree(this.selfRef.current!, this.handleFocusChange);
        // this.quill.root.addEventListener("focusin", () => this.forceUpdate());
        // this.quill.root.addEventListener("focusin", () => this.forceUpdate());
        // this.quill.on("selection-change", () =>
        // this.setState({ quillHasFocus: this.quill.root === document.activeElement }),
        // );

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
        document.removeEventListener("keydown", this.escFunction, false);
    }

    /**
     * Track the menu's focus state.
     */
    private handleFocusChange = hasFocus => {
        this.setState({ menuHasFocus: hasFocus });
    };

    /**
     * Handle create-link keyboard shortcut.
     */
    private commandKHandler = () => {
        const { lastGoodSelection } = this.props.instanceState;
        const inCodeBlock = rangeContainsBlot(this.quill, CodeBlockBlot, lastGoodSelection);

        if (!this.isOneLineOrLess || this.isLinkMenuVisible || inCodeBlock) {
            return;
        }

        const inLinkBlot = rangeContainsBlot(this.quill, LinkBlot, lastGoodSelection);

        if (inLinkBlot) {
            this.formatter.link(lastGoodSelection);
            this.reset();
        } else {
            const currentText = this.quill.getText(lastGoodSelection.index, lastGoodSelection.length);
            if (isAllowedUrl(currentText)) {
                this.setState({
                    inputValue: currentText,
                });
            }
            this.toggleLinkMenu();
        }
    };

    /**
     * Open up the link menu.
     *
     * Opens the menu if there is no current link formatting.
     */
    private toggleLinkMenu = () => {
        if (typeof this.props.activeFormats.link === "string") {
            this.setState({ isLinkMenuOpen: false });
            this.formatter.link(this.props.instanceState.lastGoodSelection);
        } else {
            this.setState({ isLinkMenuOpen: true }, () => {
                this.linkInput.current!.focus();
            });
        }
    };

    /**
     * Close the menu.
     */
    private escFunction = (event: KeyboardEvent) => {
        if (event.keyCode === 27) {
            if (this.isLinkMenuVisible) {
                event.preventDefault();
                this.clearLinkInput();
            } else if (this.isFormatMenuVisible) {
                event.preventDefault();
                this.cancel();
            }
        }
    };

    private clearLinkInput() {
        this.quill.setSelection(this.props.instanceState.lastGoodSelection);
        this.setState({ isLinkMenuOpen: false, inputValue: "" });
    }

    /**
     * Handle clicks on the link menu's close button.
     */
    private onCloseClick = (event: React.MouseEvent<any>) => {
        event.preventDefault();
        this.clearLinkInput();
    };

    /**
     * Clear the link menu's input content and hide the link menu.
     */
    private reset = () => {
        this.props.instanceState.lastGoodSelection &&
            this.quill.setSelection(this.props.instanceState.lastGoodSelection, Emitter.sources.USER);

        this.setState({
            inputValue: "",
        });
    };

    private cancel = () => {
        const { lastGoodSelection } = this.props.instanceState;
        const newSelection = {
            index: lastGoodSelection.index + lastGoodSelection.length,
            length: 0,
        };
        this.setState({
            inputValue: "",
        });
        this.quill.setSelection(newSelection);
    };

    /**
     * Handle key-presses for the link toolbar.
     */
    private onInputKeyDown = (event: React.KeyboardEvent<any>) => {
        if (Keyboard.match(event.nativeEvent, "enter")) {
            event.preventDefault();
            this.formatter.link(this.props.instanceState.lastGoodSelection, this.state.inputValue);
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
