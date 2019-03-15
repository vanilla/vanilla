/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Quill from "quill/core";
import { t } from "@library/utility/appUtils";
import { forceSelectionUpdate, isEmbedSelected } from "@rich-editor/quill/utility";
import Formatter from "@rich-editor/quill/Formatter";
import classNames from "classnames";
import FocusWatcher from "@library/dom/FocusWatcher";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import MenuItems from "@rich-editor/toolbars/pieces/MenuItems";
import { IWithEditorProps, withEditor } from "@rich-editor/editor/context";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";
import ActiveFormatIcon from "@rich-editor/toolbars/pieces/ActiveFormatIcon";
import ParagraphMenuBar from "@rich-editor/menuBar/paragraph/ParagraphMenuBar";
import {
    paragraphMenuBarClasses,
    paragraphToolbarContainerClasses,
} from "@rich-editor/menuBar/paragraph/paragraphMenuBarStyles";

interface IProps extends IWithEditorProps {
    disabled?: boolean;
    renderAbove?: boolean;
    renderLeft?: boolean;
}

interface IState {
    hasFocus: boolean;
    rovingTabIndex: number; // https://www.w3.org/TR/wai-aria-practices-1.1/#kbd_roving_tabindex
}

export class ParagraphMenuBarToggle extends React.PureComponent<IProps, IState> {
    private quill: Quill;
    private ID: string;
    private componentID: string;
    private menuID: string;
    private buttonID: string;
    private selfRef: React.RefObject<HTMLDivElement> = React.createRef();
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    private menuRef: React.RefObject<MenuItems> = React.createRef();
    private formatter: Formatter;
    private focusWatcher: FocusWatcher;

    constructor(props: IProps) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;
        this.formatter = new Formatter(this.quill);
        this.ID = this.props.editorID + "paragraphMenu";
        this.componentID = this.ID + "-component";
        this.menuID = this.ID + "-menu";
        this.buttonID = this.ID + "-button";
        this.state = {
            hasFocus: true, // do not commit
            rovingTabIndex: 0,
        };
    }

    /**
     * @inheritDoc
     */
    public componentDidMount() {
        this.focusWatcher = new FocusWatcher(this.selfRef.current!, newHasFocusState => {
            if (!newHasFocusState) {
                this.setState({ hasFocus: false });
            }
        });
        this.focusWatcher.start();
    }

    /**
     * @inheritDoc
     */
    public componentWillUnmount() {
        this.focusWatcher.stop();
    }

    public render() {
        const classesRichEditor = richEditorClasses(this.props.legacyMode);
        const classesParagraphMenuBarToggle = paragraphMenuBarClasses(this.props.legacyMode);
        let pilcrowClasses = classNames(
            { isOpen: this.isMenuVisible },
            "richEditor-button",
            "richEditorParagraphMenu-handle",
            classesRichEditor.paragraphMenuHandle,
            classesRichEditor.button,
        );

        if (!this.isPilcrowVisible || isEmbedSelected(this.quill, this.props.lastGoodSelection)) {
            pilcrowClasses += " isHidden";
        }

        return (
            <div
                id={this.componentID}
                style={this.pilcrowStyles}
                className={classNames({ isMenuInset: !this.props.legacyMode }, classesParagraphMenuBarToggle.toggle)}
                onKeyDown={this.handleKeyDown}
                ref={this.selfRef}
            >
                <button
                    type="button"
                    id={this.buttonID}
                    ref={this.buttonRef}
                    aria-label={t("Line Level Formatting Menu")}
                    aria-controls={this.menuID}
                    aria-expanded={this.isMenuVisible}
                    disabled={!this.isPilcrowVisible}
                    className={pilcrowClasses}
                    aria-haspopup="menu"
                    onClick={this.pilcrowClickHandler}
                    onKeyDown={this.handlePilcrowKeyDown}
                >
                    <ActiveFormatIcon activeFormats={this.props.activeFormats} />
                </button>
                <div id={this.menuID} className={this.dropDownClasses} style={this.toolbarStyles} role="menu">
                    <ParagraphMenuBar
                        menuRef={this.menuRef}
                        formatter={this.formatter}
                        afterClickHandler={this.close}
                        activeFormats={this.props.activeFormats}
                        lastGoodSelection={this.props.lastGoodSelection}
                    />
                </div>
            </div>
        );
    }

    /**
     * Determine whether or not we should show pilcrow at all.
     */
    private get isPilcrowVisible() {
        const { currentSelection } = this.props;
        if (!currentSelection) {
            return false;
        }
        return true;
    }

    /**
     * Show the menu if we have a valid selection, and a valid focus.
     */
    private get isMenuVisible() {
        return !!this.props.lastGoodSelection; // do not commit
    }

    /**
     * Get the inline styles for the pilcrow. This is mostly just positioning it on the Y access currently.
     */
    private get pilcrowStyles(): React.CSSProperties {
        if (!this.props.lastGoodSelection) {
            return {};
        }
        const bounds = this.quill.getBounds(this.props.lastGoodSelection.index, this.props.lastGoodSelection.length);

        // This is the pixel offset from the top needed to make things align correctly.

        return {
            top: (bounds.top + bounds.bottom) / 2 - this.verticalOffset,
        };
    }

    private static readonly DEFAULT_OFFSET = 2;
    private static readonly LEGACY_EXTRA_OFFSET = 2;

    private get verticalOffset(): number {
        const calculatedOffset =
            parseInt(window.getComputedStyle(this.quill.root).paddingTop!, 10) || ParagraphMenuBarToggle.DEFAULT_OFFSET;
        const extraOffset = this.props.legacyMode ? ParagraphMenuBarToggle.LEGACY_EXTRA_OFFSET : 0;
        return calculatedOffset + extraOffset;
    }

    /**
     * Get the classes for the toolbar.
     */
    private get dropDownClasses(): string {
        if (!this.props.lastGoodSelection) {
            return "";
        }
        const bounds = this.quill.getBounds(this.props.lastGoodSelection.index, this.props.lastGoodSelection.length);
        const classes = paragraphMenuBarClasses();
        const classesDropDown = dropDownClasses();
        return classNames(
            "richEditor-toolbarContainer",
            "richEditor-paragraphToolbarContainer",
            classes.position,
            { likeDropDownContent: !this.props.legacyMode },
            !this.props.legacyMode ? classesDropDown.likeDropDownContent : "",
            bounds.top <= 30 ? "isDown" : "isUp",
        );
    }

    /**
     * Get the inline styles for the toolbar. This just a matter of hiding it.
     * This could likely be replaced by a CSS class in the future.
     */
    private get toolbarStyles(): React.CSSProperties {
        if (this.isMenuVisible && !isEmbedSelected(this.quill, this.props.lastGoodSelection)) {
            return {};
        } else {
            // We hide the toolbar when its not visible.
            return {
                visibility: "hidden",
                position: "absolute",
                zIndex: -1,
            };
        }
    }

    /**
     * Click handler for the Pilcrow
     */
    private pilcrowClickHandler = (event: React.MouseEvent<any>) => {
        event.preventDefault();
        this.setState({ hasFocus: true }, () => {
            if (this.state.hasFocus) {
                this.menuRef.current!.focusFirstItem();
                forceSelectionUpdate();
            }
        });
    };

    /**
     * Close the paragraph menu and place the selection at the end of the current selection if there is one.
     */
    private close = () => {
        this.setState({ hasFocus: true });
        const { lastGoodSelection } = this.props;
        const newSelection = {
            index: lastGoodSelection.index + lastGoodSelection.length,
            length: 0,
        };
        this.quill.setSelection(newSelection);
    };

    /**
     * Handle the escape key. when the toolbar is open. Note that focus still goes back to the main button,
     * but the selection is set to a 0 length selection at the end of the current selection before the
     * focus is moved.
     */
    private handleKeyDown = (event: React.KeyboardEvent) => {
        if (event.keyCode === 27 && this.state.hasFocus) {
            event.preventDefault();
            this.close();
        }
    };

    /**
     * From an accessibility point of view, this is a Editor Menubar. The only difference is it has a toggled visibility
     *
     * @see https://www.w3.org/TR/wai-aria-practices-1.1/examples/menubar/menubar-2/menubar-2.html
     */
    private handlePilcrowKeyDown = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
            // Opens submenu and moves focus to first item in the submenu.
            case "Space":
            case "Enter":
                break;
            // If a submenu is open, closes it. Otherwise, does nothing.
            case "Escape":
                break;
            // Moves focus to first item in the menubar.
            case "Home":
                break;
            // 	Moves focus to last item in the menubar.
            case "End":
                break;
            // Moves focus to the next item in the menubar.
            // If focus is on the last item, moves focus to the first item.
            case "ArrowRight":
                break;
            // Moves focus to the previous item in the menubar.
            // If focus is on the first item, moves focus to the last item.
            case "ArrowLeft":
                break;
            // 	Opens submenu and moves focus to last item in the submenu.
            case "ArrowUp":
                event.preventDefault();
                this.setState({ hasFocus: true }, () => {
                    this.menuRef.current!.focusFirstItem();
                });
                break;
            // Opens submenu and moves focus to first item in the submenu.
            case "ArrowDown":
                event.preventDefault();
                this.setState({ hasFocus: true }, () => {
                    this.menuRef.current!.focusLastItem();
                });
                break;
            // Moves focus to next item in the menubar having a name that starts with the typed character.
            // If none of the items have a name starting with the typed character, focus does not move.
            default:
                break;
        }
    };
}

export default withEditor<IProps>(ParagraphMenuBarToggle);
