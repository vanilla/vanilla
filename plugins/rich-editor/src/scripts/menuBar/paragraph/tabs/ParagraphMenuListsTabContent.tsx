/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { paragraphMenuTabsClasses } from "@rich-editor/menuBar/paragraph/paragraphMenuBarStyles";
import { IMenuBarItemTypes } from "@rich-editor/menuBar/paragraph/ParagraphMenusBarToggle";

export interface IMenuCheckRadio {
    checked: boolean;
    icon: JSX.Element;
    text: string;
}

interface IProps {
    items: IMenuCheckRadio[];
    label: string;
    activeIndex: number | null;
    type: IMenuBarItemTypes;
    handleClick: () => void;
    heading: 1 | 2 | 3 | 4 | 5 | null;
}

interface IState {
    heading: 1 | 2 | 3 | 4 | 5 | null;
}

/**
 * Implemented tab content for menu list
 */
export default class ParagraphMenuListsTabContent extends React.Component<IProps> {
    public render() {
        if (this.props.items.length > 0) {
            const classes = paragraphMenuTabsClasses();
            return <div className={classes.content} />;
        } else {
            return null;
        }
    }
}
