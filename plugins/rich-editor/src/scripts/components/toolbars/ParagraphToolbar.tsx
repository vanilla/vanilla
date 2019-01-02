/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Quill from "quill/core";
import { t } from "@library/application";
import { withEditor, IWithEditorProps } from "@rich-editor/components/context";
import { isEmbedSelected, forceSelectionUpdate } from "@rich-editor/quill/utility";
import Formatter from "@rich-editor/quill/Formatter";
import ParagraphToolbarMenuItems from "@rich-editor/components/toolbars/pieces/ParagraphToolbarMenuItems";
import MenuItems from "@rich-editor/components/toolbars/pieces/MenuItems";
import classNames from "classnames";
import FocusWatcher from "@library/FocusWatcher";
import ActiveFormatIcon from "@rich-editor/components/toolbars/pieces/ActiveFormatIcon";

interface IProps extends IWithEditorProps {}

interface IState {
    hasFocus: boolean;
}

export class ParagraphToolbar extends React.PureComponent<IProps, IState> {
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
            hasFocus: false,
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
        let pilcrowClasses = classNames(
            { isOpen: this.isMenuVisible },
            "richEditor-button",
            "richEditorParagraphMenu-handle",
        );

        if (!this.isPilcrowVisible || isEmbedSelected(this.quill, this.props.lastGoodSelection)) {
            pilcrowClasses += " isHidden";
        }

        return (
            <div
                id={this.componentID}
                style={this.pilcrowStyles}
                className={classNames("richEditorParagraphMenu", { isMenuInset: !this.props.legacyMode })}
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
                <div id={this.menuID} className={this.toolbarClasses} style={this.toolbarStyles} role="menu">
                    <ParagraphToolbarMenuItems
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
        return !!this.props.lastGoodSelection && this.state.hasFocus;
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
            parseInt(window.getComputedStyle(this.quill.root).paddingTop!, 10) || ParagraphToolbar.DEFAULT_OFFSET;
        const extraOffset = this.props.legacyMode ? ParagraphToolbar.LEGACY_EXTRA_OFFSET : 0;
        return calculatedOffset + extraOffset;
    }

    /**
     * Get the classes for the toolbar.
     */
    private get toolbarClasses(): string {
        if (!this.props.lastGoodSelection) {
            return "";
        }
        const bounds = this.quill.getBounds(this.props.lastGoodSelection.index, this.props.lastGoodSelection.length);
        let classes = "richEditor-toolbarContainer richEditor-paragraphToolbarContainer";

        if (!this.props.legacyMode) {
            classes += " likeDropDownContent";
        }

        if (bounds.top > 30) {
            classes += " isUp";
        } else {
            classes += " isDown";
        }

        return classes;
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
        this.setState({ hasFocus: false });
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
     * Implement opening/closing keyboard shortcuts in accordance with the WAI-ARIA best practices for menuitems.
     *
     * @see https://www.w3.org/TR/wai-aria-practices/examples/menubar/menubar-2/menubar-2.html
     */
    private handlePilcrowKeyDown = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
            case "ArrowUp":
                event.preventDefault();
                this.setState({ hasFocus: true }, () => {
                    this.menuRef.current!.focusFirstItem();
                });
                break;
            case "ArrowDown":
                event.preventDefault();
                this.setState({ hasFocus: true }, () => {
                    this.menuRef.current!.focusLastItem();
                });
                break;
        }
    };
}

export default withEditor<IProps>(ParagraphToolbar);
