/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ParagraphMenuBarRadioGroup, {
    IMenuBarRadioButton,
} from "@rich-editor/menuBar/paragraph/items/ParagraphMenuBarRadioGroup";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import { richEditorClasses } from "@rich-editor/editor/richEditorStyles";

interface IProps {
    items: IMenuBarRadioButton[];
    closeMenu: () => void;
    closeMenuAndSetCursor: () => void;
    className?: string;
    setRovingIndex: () => void;
    disabled?: boolean;
}

/**
 * Implemented contents of heading menu
 */
export default class ParagraphMenuHeadingsTabContent extends React.Component<IProps> {
    public render() {
        const classes = richEditorClasses(false);
        const handleClick = (data: IMenuBarRadioButton, index: number) => {
            this.props.items[index].formatFunction();
            this.props.setRovingIndex();
            this.props.closeMenuAndSetCursor();
        };
        return (
            <ParagraphMenuBarRadioGroup
                className={classNames(classes.paragraphMenuPanel, this.props.className)}
                handleClick={handleClick}
                label={t("Headings")}
                items={this.props.items}
                disabled={this.props.disabled}
            />
        );
    }
}
