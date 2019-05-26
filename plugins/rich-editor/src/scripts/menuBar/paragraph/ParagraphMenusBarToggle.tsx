/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import FocusWatcher from "@library/dom/FocusWatcher";
import TabHandler from "@library/dom/TabHandler";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import {
    blockquote,
    codeBlock,
    heading2,
    heading3,
    heading4,
    heading5,
    listOrdered,
    listUnordered,
    spoiler,
} from "@library/icons/editorIcons";
import { srOnly } from "@library/styles/styleHelpers";
import { t } from "@library/utility/appUtils";
import { IWithEditorProps } from "@rich-editor/editor/context";
import { withEditor } from "@rich-editor/editor/withEditor";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";
import { menuState } from "@rich-editor/menuBar/paragraph/formats/formatting";
import ParagraphMenuBar from "@rich-editor/menuBar/paragraph/ParagraphMenuBar";
import Formatter from "@rich-editor/quill/Formatter";
import { isEmbedSelected } from "@rich-editor/quill/utility";
import ActiveFormatIcon from "@rich-editor/toolbars/pieces/ActiveFormatIcon";
import classNames from "classnames";
import Quill, { RangeStatic } from "quill/core";
import React from "react";
import { style } from "typestyle";
import uniqueId from "lodash/uniqueId";
import { IconForButtonWrap } from "@rich-editor/editor/pieces/IconForButtonWrap";

export enum IMenuBarItemTypes {
    CHECK = "checkbox",
    RADIO = "radiobutton",
    SEPARATOR = "separator",
}

interface IProps extends IWithEditorProps {
    disabled?: boolean;
    mobile?: boolean;
    lastGoodSelection: RangeStatic;
    renderAbove?: boolean;
}

interface IState {
    hasFocus: boolean;
    rovingTabIndex: number; // https://www.w3.org/TR/wai-aria-practices-1.1/#kbd_roving_tabindex
}

// Implements the paragraph menubar
export class ParagraphMenusBarToggle extends React.PureComponent<IProps, IState> {
    private quill: Quill;
    private ID: string;
    private componentID: string;
    private menuID: string;
    private buttonID: string;
    private selfRef: React.RefObject<HTMLDivElement> = React.createRef();
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    private menusRef: React.RefObject<HTMLDivElement> = React.createRef();
    private panelsRef: React.RefObject<HTMLDivElement> = React.createRef();
    private focusWatcher: FocusWatcher;

    constructor(props: IProps) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill!;
        this.ID = uniqueId("paragraphMenu") + "-formattingMenus";
        this.componentID = this.ID + "-component";
        this.menuID = this.ID + "-menu";
        this.buttonID = this.ID + "-button";
        this.state = {
            hasFocus: false,
            rovingTabIndex: 0,
            // activeMenu: null,
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

    /**
     * @inheritDoc
     */
    public render() {
        const classesRichEditor = richEditorClasses(this.props.legacyMode);
        const classes = richEditorClasses(this.props.legacyMode);
        let pilcrowClasses = classNames(
            { isOpen: this.isMenuVisible },
            classesRichEditor.paragraphMenuHandle,
            this.props.mobile ? classesRichEditor.paragraphMenuHandleMobile : "",
        );

        if (!this.props.mobile) {
            if (
                (!this.isPilcrowVisible && !this.isMenuVisible) ||
                isEmbedSelected(this.quill, this.props.lastGoodSelection)
            ) {
                pilcrowClasses += " isHidden";
            }
        }

        const formatter = new Formatter(this.quill, this.props.lastGoodSelection);
        const menuActiveFormats = menuState(formatter, this.props.activeFormats);
        const topLevelIcons = this.topLevelIcons(menuActiveFormats);

        return (
            <div
                id={this.componentID}
                style={this.pilcrowStyles}
                className={classNames(
                    { isMenuInset: !this.props.legacyMode },
                    this.props.mobile ? classes.paragraphMenuMobile : classes.paragraphMenu,
                )}
                onKeyDown={this.handleMenuBarKeyDown}
                ref={this.selfRef}
            >
                <button
                    type="button"
                    id={this.buttonID}
                    ref={this.buttonRef}
                    aria-label={t("Toggle Paragraph Format Menu")}
                    aria-controls={this.menuID}
                    aria-expanded={this.isMenuVisible}
                    disabled={this.props.disabled}
                    className={classNames(pilcrowClasses, {
                        [classes.button]: this.props.mobile,
                        [classes.menuItem]: this.props.mobile,
                    })}
                    aria-haspopup="menu"
                    onClick={this.pilcrowClickHandler}
                    onKeyDown={this.handleEscape}
                >
                    <IconForButtonWrap icon={<ActiveFormatIcon activeFormats={menuActiveFormats} />} />
                </button>
                <div
                    id={this.menuID}
                    className={classNames(
                        this.dropDownClasses,
                        classes.menuBar,
                        this.isMenuVisible ? "" : style(srOnly()),
                    )}
                    style={this.toolbarStyles}
                    role="menu"
                >
                    <ParagraphMenuBar
                        menusRef={this.menusRef}
                        panelsRef={this.panelsRef}
                        parentID={this.ID}
                        label={"Paragraph Format Menu"}
                        isMenuVisible={this.isMenuVisible}
                        lastGoodSelection={this.props.lastGoodSelection}
                        legacyMode={this.props.legacyMode}
                        close={this.close}
                        formatter={formatter}
                        menuActiveFormats={menuActiveFormats}
                        rovingIndex={this.state.rovingTabIndex}
                        setRovingIndex={this.setRovingIndex}
                        topLevelIcons={topLevelIcons}
                    />
                </div>
            </div>
        );
    }

    /**
     * Determine whether or not we should show pilcrow at all.
     */
    private get isPilcrowVisible() {
        return this.props.currentSelection;
    }

    private topLevelIcons = menuActiveFormats => {
        let headingMenuIcon = heading2();
        if (menuActiveFormats.headings.heading3) {
            headingMenuIcon = heading3();
        } else if (menuActiveFormats.headings.heading4) {
            headingMenuIcon = heading4();
        } else if (menuActiveFormats.headings.heading5) {
            headingMenuIcon = heading5();
        }

        let specialBlockMenuIcon = blockquote();
        if (menuActiveFormats.specialFormats.codeBlock) {
            specialBlockMenuIcon = codeBlock();
        } else if (menuActiveFormats.specialFormats.spoiler) {
            specialBlockMenuIcon = spoiler();
        }

        let listMenuIcon = listUnordered();
        if (menuActiveFormats.lists.ordered) {
            listMenuIcon = listOrdered();
        }

        return {
            headingMenuIcon,
            listMenuIcon,
            specialBlockMenuIcon,
        };
    };

    /**
     * Show the menu if we have a valid selection, and a valid focus.
     */
    private get isMenuVisible() {
        return !!this.props.lastGoodSelection && this.state.hasFocus;
    }

    private setRovingIndex = (index: number, callback?: () => void) => {
        this.setState(
            {
                rovingTabIndex: index,
            },
            () => {
                callback && callback();
            },
        );
    };

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
            parseInt(window.getComputedStyle(this.quill.root).paddingTop!, 10) ||
            ParagraphMenusBarToggle.DEFAULT_OFFSET;
        const extraOffset = this.props.legacyMode ? ParagraphMenusBarToggle.LEGACY_EXTRA_OFFSET : 0;
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
        const scrollBounds = this.quill.scroll.domNode.getBoundingClientRect();
        const classes = richEditorClasses(this.props.legacyMode);
        const classesDropDown = dropDownClasses();

        return classNames(
            classes.position,
            classes.menuBar,
            classesDropDown.likeDropDownContent,
            this.props.renderAbove || scrollBounds.height - bounds.bottom <= 170 ? "isUp" : "isDown",
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
        this.setState({ hasFocus: !this.state.hasFocus }, () => {
            if (this.state.hasFocus && this.menusRef.current) {
                const menu: HTMLElement = this.menusRef.current!;
                const tabHandler = new TabHandler(menu);
                const nextEl = tabHandler.getInitial();
                if (nextEl) {
                    nextEl.focus();
                }
            }
        });
    };

    /**
     * Close the paragraph menu and place the selection at the end of the current selection if there is one.
     */
    private close = (focusToggle: boolean = false) => {
        this.setState({ hasFocus: false });
        const { lastGoodSelection } = this.props;
        if (!focusToggle) {
            const newSelection = {
                index: lastGoodSelection.index + lastGoodSelection.length,
                length: 0,
            };
            this.quill.setSelection(newSelection);
        } else {
            this.buttonRef && this.buttonRef.current && this.buttonRef.current.focus();
        }
    };

    /**
     * Handle the escape key. when the toolbar is open. Note that focus still goes back to the main button,
     * but the selection is set to a 0 length selection at the end of the current selection before the
     * focus is moved.
     */
    private handleEscape = (event: React.KeyboardEvent) => {
        if (event.key === "Escape" && this.state.hasFocus) {
            event.preventDefault();
            this.close();
        }
    };

    /**
     * From an accessibility point of view, this is a Editor Menubar. The only difference is it has a toggled visibility
     *
     * @see https://www.w3.org/TR/wai-aria-practices-1.1/examples/menubar/menubar-2/menubar-2.html
     */
    private handleMenuBarKeyDown = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
            // If a submenu is open, closes it. Otherwise, does nothing.
            case "Escape":
                event.preventDefault();
                if (this.state.hasFocus) {
                    event.preventDefault();
                    this.close();
                }
                break;
        }
    };
}

export default withEditor<IProps>(ParagraphMenusBarToggle);
