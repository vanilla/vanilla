/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";

interface IProps {}

interface IState {}

/**
 * Implemented ParagraphMenuDropDown component, this is for mobile
 */
export default class ParagraphMenuRadio extends React.Component<IProps, IState> {
    public render() {
        return null;
    }

    /**
     * Implement keyboard shortcuts in accordance with the WAI-ARIA best practices for Submenu.
     *
     * @see https://www.w3.org/TR/wai-aria-practices-1.1/examples/menubar/menubar-2/menubar-2.html
     */
    private handleKeyDown = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
            // Activates menu item, causing action to be executed, e.g., bold text, change font.
            case "Space":
            case "Enter":
                break;
        }
    };
}
