/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { paragraphMenuTabsClasses } from "@rich-editor/menuBar/paragraph/paragraphMenuBarStyles";
import { t } from "@library/utility/appUtils";
import ParagraphMenuBarRadioGroup, {
    IMenuBarRadioButton,
} from "@rich-editor/menuBar/paragraph/items/ParagraphMenuBarRadioGroup";
import classNames from "classnames";

interface IProps {
    items: IMenuBarRadioButton[];
    closeMenuAndSetCursor: () => void;
    className?: string;
}

/**
 * Implemented tab content for block styles
 */
export default class ParagraphMenuBlockTabContent extends React.Component<IProps> {
    public render() {
        const classes = paragraphMenuTabsClasses();
        const handleClick = (data: IMenuBarRadioButton, index: number) => {
            this.props.items[index].formatFunction();
            this.props.closeMenuAndSetCursor();
        };
        return (
            <ParagraphMenuBarRadioGroup
                className={classNames(classes.panel, this.props.className)}
                handleClick={handleClick}
                label={t("Special Formats")}
                items={this.props.items}
            />
        );
    }
}
