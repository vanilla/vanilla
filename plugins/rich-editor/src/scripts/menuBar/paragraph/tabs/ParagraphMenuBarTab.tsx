/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { paragraphMenuBarClasses } from "@rich-editor/menuBar/paragraph/paragraphMenuBarStyles";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";

interface IProps {
    accessibleButtonLabel: string;
    className?: string;
    index: number;
    parentID: string;
    isMenuVisible: boolean;
    toggleMenu: () => void;
    icon: JSX.Element;
    tabComponent: React.ReactNode;
    rovingIndex: number;
    setRovingIndex: (index: number) => void;
    activeFormats: {} | boolean;
    legacyMode: boolean;
}

interface IState {
    open: boolean;
}

/**
 * Implemented generic tab component.
 */
export default class ParagraphMenuBarTab extends React.Component<IProps, IState> {
    private ID;
    private componentID;
    private menuID;
    private buttonID;
    private selfRef;
    private buttonRef;

    constructor(props: IProps) {
        super(props);
        this.ID = this.props.parentID + `${this.props.parentID}-dropDown`;
        this.componentID = this.ID + "-component";
        this.menuID = this.ID + "-menu";
        this.buttonID = this.ID + "-button";
        this.state = {
            open: false,
        };
    }

    public render() {
        const { className, rovingIndex, index, isMenuVisible, toggleMenu, children, icon } = this.props;
        if (open) {
            const classes = paragraphMenuBarClasses();
            const classesRichEditor = richEditorClasses(this.props.legacyMode);
            // If the roving index matches my index, or no roving index is set and we're on the first tab
            const tabIndex = rovingIndex === index || (rovingIndex === null && index === 0) ? 0 : -1;
            return (
                <div id={this.componentID} ref={this.selfRef} tabIndex={tabIndex} className={classNames(className)}>
                    <button
                        type="button"
                        role="menuitem"
                        id={this.buttonID}
                        ref={this.buttonRef}
                        aria-label={this.props.accessibleButtonLabel}
                        title={this.props.accessibleButtonLabel}
                        aria-controls={this.menuID}
                        aria-expanded={isMenuVisible}
                        aria-haspopup="menu"
                        onClick={toggleMenu}
                        className={classesRichEditor.button}
                    >
                        {icon}
                        <ScreenReaderContent>{this.props.accessibleButtonLabel}</ScreenReaderContent>
                    </button>
                    {this.state.open && (
                        <div id={this.menuID} role="menu">
                            {children}
                        </div>
                    )}
                </div>
            );
        } else {
            return null;
        }
    }
}
