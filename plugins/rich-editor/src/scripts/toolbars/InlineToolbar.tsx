/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Quill from "quill/core";
import Emitter from "quill/core/emitter";
import Keyboard from "quill/modules/keyboard";
import LinkBlot from "quill/formats/link";
import { t, isAllowedUrl } from "@library/utility/appUtils";
import { IWithEditorProps } from "@rich-editor/editor/context";
import { withEditor } from "@rich-editor/editor/withEditor";
import { rangeContainsBlot } from "@rich-editor/quill/utility";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import Formatter from "@rich-editor/quill/Formatter";
import FocusWatcher from "@library/dom/FocusWatcher";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import classNames from "classnames";
import ToolbarContainer from "@rich-editor/toolbars/pieces/ToolbarContainer";
import InlineToolbarMenuItems from "@rich-editor/toolbars/pieces/InlineToolbarMenuItems";
import InlineToolbarLinkInput from "@rich-editor/toolbars/pieces/InlineToolbarLinkInput";

interface IProps extends IWithEditorProps {}

interface IState {
    inputValue: string;
    isLinkMenuOpen: boolean;
    menuHasFocus: boolean;
}

/**
 * This __cannot__ be a pure component because it needs to re-render when quill emits, even if the selection is the same.
 */
export class InlineToolbar extends React.PureComponent<IProps, IState> {
    private quill: Quill;
    private linkInput: React.RefObject<HTMLInputElement> = React.createRef();
    private selfRef: React.RefObject<HTMLDivElement> = React.createRef();
    private focusWatcher: FocusWatcher;

    /**
     * Temporaly remove the focus requirement on the toolbar so we can focus it.
     * @see https://github.com/vanilla/rich-editor/pull/107
     */
    private ignoreLinkToolbarFocusRequirement: boolean = false;

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;

        this.state = {
            inputValue: "",
            isLinkMenuOpen: false,
            menuHasFocus: false,
        };
    }

    private get formatter(): Formatter {
        return new Formatter(this.quill, this.props.lastGoodSelection);
    }

    /**
     * Reset the link menu state when the selection changes.
     */
    public componentDidUpdate(prevProps: IProps) {
        const selection = this.props.lastGoodSelection;
        const prevSelection = prevProps.lastGoodSelection;
        if (prevSelection.index !== selection.index || prevSelection.length !== selection.length) {
            this.setState({ isLinkMenuOpen: false });
        }
    }

    public render() {
        const classes = dropDownClasses();
        const { activeFormats } = this.props;
        const alertMessage = this.isFormatMenuVisible ? (
            <span aria-live="assertive" role="alert" className="sr-only">
                {t("Inline Menu Available")}
            </span>
        ) : null;

        return (
            <div ref={this.selfRef}>
                <ToolbarContainer selection={this.props.lastGoodSelection} isVisible={this.isFormatMenuVisible}>
                    {alertMessage}
                    <InlineToolbarMenuItems
                        formatter={this.formatter}
                        onLinkClick={this.toggleLinkMenu}
                        activeFormats={activeFormats}
                        lastGoodSelection={this.props.lastGoodSelection}
                        className={classNames("likeDropDownContent", classes.likeDropDownContent)}
                    />
                </ToolbarContainer>
                <ToolbarContainer selection={this.props.lastGoodSelection} isVisible={this.isLinkMenuVisible}>
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
        const inCodeBlock = rangeContainsBlot(this.quill, CodeBlockBlot, this.props.lastGoodSelection);
        return (
            this.state.isLinkMenuOpen &&
            (this.hasFocus || this.ignoreLinkToolbarFocusRequirement) &&
            this.isOneLineOrLess &&
            !inCodeBlock &&
            this.selectionHasContent
        );
    }

    /**
     * Determine visibility of the formatting menu.
     */
    private get isFormatMenuVisible(): boolean {
        const selectionHasLength = this.props.lastGoodSelection.length > 0;
        const inCodeBlock = rangeContainsBlot(this.quill, CodeBlockBlot, this.props.lastGoodSelection);
        return (
            !this.isLinkMenuVisible &&
            this.hasFocus &&
            selectionHasLength &&
            this.isOneLineOrLess &&
            !inCodeBlock &&
            this.selectionHasContent
        );
    }

    private get selectionHasContent(): boolean {
        const { lastGoodSelection } = this.props;
        const text = this.quill.getText(lastGoodSelection.index, lastGoodSelection.length);
        return !!text && text !== "\n";
    }

    /**
     * Determine if our selection spreads over multiple lines or not.
     */
    private get isOneLineOrLess(): boolean {
        const { lastGoodSelection } = this.props;
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
        this.quill.root.addEventListener("keydown", this.escFunction, false);
        this.focusWatcher = new FocusWatcher(this.selfRef.current!, this.handleFocusChange);
        this.focusWatcher.start();

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
        this.quill.root.removeEventListener("keydown", this.escFunction, false);
        this.focusWatcher.stop();
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
        const { lastGoodSelection } = this.props;
        const inCodeBlock = rangeContainsBlot(this.quill, CodeBlockBlot, lastGoodSelection);

        if (!this.isOneLineOrLess || this.isLinkMenuVisible || inCodeBlock) {
            return;
        }

        const inLinkBlot = rangeContainsBlot(this.quill, LinkBlot, lastGoodSelection);

        if (inLinkBlot) {
            this.formatter.link();
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
    private toggleLinkMenu = (event?: React.MouseEvent<any>) => {
        event && event.preventDefault();
        if (typeof this.props.activeFormats.link === "string") {
            this.setState({ isLinkMenuOpen: false });
            this.formatter.link();
        } else {
            this.ignoreLinkToolbarFocusRequirement = true;
            this.setState({ isLinkMenuOpen: true }, () => {
                this.linkInput.current!.focus();
                this.ignoreLinkToolbarFocusRequirement = false;
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
                event.stopPropagation();
                event.stopImmediatePropagation();
                this.clearLinkInput();
            } else if (this.isFormatMenuVisible) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                this.cancel();
            }
        }
    };

    /**
     * Clear the link input, focus quill, and restore the selection.
     */
    private clearLinkInput() {
        this.setState({ isLinkMenuOpen: false, inputValue: "" });
        this.quill.focus();
        this.quill.setSelection(this.props.lastGoodSelection);
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
        this.props.lastGoodSelection && this.quill.setSelection(this.props.lastGoodSelection, Emitter.sources.USER);

        this.setState({
            inputValue: "",
        });
    };

    private cancel = () => {
        const { lastGoodSelection } = this.props;
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
    private onInputKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
        if (Keyboard.match(event.nativeEvent, "enter")) {
            event.preventDefault();
            this.formatter.link(this.state.inputValue);
            this.clearLinkInput();
        }

        this.escFunction(event.nativeEvent);
    };

    /**
     * Handle changes to the the close menu's input.
     */
    private onInputChange = (event: React.ChangeEvent<any>) => {
        this.setState({ inputValue: event.target.value });
    };
}

export default withEditor<IProps>(InlineToolbar);
