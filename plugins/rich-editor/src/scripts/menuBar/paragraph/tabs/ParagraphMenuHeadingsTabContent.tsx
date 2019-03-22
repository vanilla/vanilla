/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ParagraphMenuBarRadioGroup, {
    IMenuBarRadioButton,
} from "@rich-editor/menuBar/paragraph/items/ParagraphMenuBarRadioGroup";
import { t } from "@library/utility/appUtils";
import { paragraphMenuTabsClasses } from "@rich-editor/menuBar/paragraph/paragraphMenuBarStyles";

interface IProps {
    items: IMenuBarRadioButton[];
    closeMenuAndSetCursor: () => void;
}

/**
 * Implemented tab content for the headings section
 */
export default class ParagraphMenuHeadingsTabContent extends React.Component<IProps> {
    public render() {
        const classes = paragraphMenuTabsClasses();
        const handleClick = (data: IMenuBarRadioButton, index: number) => {
            this.props.items[index].formatFunction();
            this.props.closeMenuAndSetCursor();
        };
        return (
            <ParagraphMenuBarRadioGroup
                className={classes.panel}
                handleClick={handleClick}
                label={t("Headings")}
                items={this.props.items}
            />
        );
    }
}
