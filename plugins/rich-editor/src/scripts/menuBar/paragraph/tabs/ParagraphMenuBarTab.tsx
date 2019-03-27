/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";

interface IProps {
    accessibleButtonLabel: string;
    className?: string;
    index: number;
    parentID: string;
    isMenuVisible: boolean; // The whole paragraph menu, not just this one
    toggleMenu: (callback?: () => void) => void;
    icon: JSX.Element;
    tabComponent: React.ReactNode;
    setRovingIndex: () => void;
    activeFormats: {} | boolean;
    legacyMode: boolean;
    tabIndex: 0 | -1;
    open: boolean;
}

/**
 * Implemented Paragraph menu bar "tab" component (which is really a menu, but looks visually mors like tabs
 */
export default class ParagraphMenuBarTab extends React.PureComponent<IProps> {
    private ID;
    private componentID;
    private menuID;
    private buttonID;
    private selfRef;
    private toggleButtonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    private handleClick;

    constructor(props: IProps) {
        super(props);
        this.ID = this.props.parentID + `${this.props.parentID}-dropDown`;
        this.componentID = this.ID + "-component";
        this.menuID = this.ID + "-menu";
        this.buttonID = this.ID + "-button";

        this.handleClick = (event: React.MouseEvent) => {
            this.props.setRovingIndex();
            this.props.toggleMenu(() => {
                this.toggleButtonRef.current && this.toggleButtonRef.current.focus();
            });
        };
    }

    public render() {
        const { className, isMenuVisible, toggleMenu, children, icon } = this.props;
        if (open) {
            const classes = richEditorClasses(this.props.legacyMode);

            // If the roving index matches my index, or no roving index is set and we're on the first tab
            return (
                <div id={this.componentID} ref={this.selfRef} className={classNames(className)}>
                    <button
                        type="button"
                        role="menuitem"
                        id={this.buttonID}
                        aria-label={this.props.accessibleButtonLabel}
                        title={this.props.accessibleButtonLabel}
                        aria-controls={this.menuID}
                        aria-expanded={isMenuVisible}
                        aria-haspopup="menu"
                        onClick={this.handleClick}
                        className={classNames(classes.button, this.props.open ? classes.topLevelButtonActive : "")}
                        tabIndex={this.props.tabIndex}
                        ref={this.toggleButtonRef}
                        onKeyDown={this.handleMenuBarKeyDown}
                    >
                        {icon}
                        <ScreenReaderContent>{this.props.accessibleButtonLabel}</ScreenReaderContent>
                    </button>
                </div>
            );
        } else {
            return null;
        }
    }

    public componentDidMount() {
        if (!this.props.open && this.props.tabIndex === 0) {
            this.toggleButtonRef.current && this.toggleButtonRef.current.focus();
        }
    }

    public componentDidUpdate() {
        if (!this.props.open && this.props.tabIndex === 0) {
            this.toggleButtonRef.current && this.toggleButtonRef.current.focus();
        }
    }

    public getMenuContentsID() {
        return this.menuID;
    }

    /**
     * From an accessibility point of view, this is a Editor Menubar. The only difference is it has a toggled visibility
     *
     * @see https://www.w3.org/TR/wai-aria-practices-1.1/examples/menubar/menubar-2/menubar-2.html
     */
    private handleMenuBarKeyDown = (event: React.KeyboardEvent<any>) => {
        switch (`${event.key}${event.shiftKey ? "-Shift" : ""}`) {
            // Opens submenu and moves focus to first item in the submenu.
            case "ArrowDown":
                if (!this.props.open) {
                    event.preventDefault();
                    this.handleClick();
                }
                break;
        }
    };
}
