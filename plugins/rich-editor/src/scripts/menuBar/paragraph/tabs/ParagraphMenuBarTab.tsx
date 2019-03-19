/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import { paragraphMenuBarClasses } from "@rich-editor/menuBar/paragraph/paragraphMenuBarStyles";

interface IProps {
    className?: string;
    rovingIndex: number;
    index: number;
    parentID: string;
    isMenuVisible: boolean;
    toggleMenu: () => void;
    icon: JSX.Element;
    disabled: boolean;
    children: React.ReactNode;
}

/**
 * Implemented generic tab component.
 */
export default class ParagraphMenuBarTab extends React.PureComponent<IProps> {
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
    }

    public render() {
        const classes = paragraphMenuBarClasses();
        const { className, rovingIndex, index, isMenuVisible, toggleMenu, icon, disabled, children } = this.props;
        // If the roving index matches my index, or no roving index is set and we're on the first tab
        const tabIndex = rovingIndex === index || (rovingIndex === null && index === 0) ? 0 : -1;
        return (
            <div
                id={this.componentID}
                ref={this.selfRef}
                tabIndex={tabIndex}
                className={classNames(className, classes.toggle)}
            >
                <button
                    type="button"
                    id={this.buttonID}
                    ref={this.buttonRef}
                    aria-label={t("Line Level Formatting Menu")}
                    aria-controls={this.menuID}
                    aria-expanded={isMenuVisible}
                    disabled={disabled}
                    aria-haspopup="menu"
                    onClick={toggleMenu}
                    className={classes.toggle}
                >
                    {icon}
                </button>
                {isMenuVisible &&
                    !disabled && (
                        <div id={this.menuID} role="menu">
                            {children}
                        </div>
                    )}
            </div>
        );
    }
}
