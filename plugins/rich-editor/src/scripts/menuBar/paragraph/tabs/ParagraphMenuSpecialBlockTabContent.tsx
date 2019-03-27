/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import ParagraphMenuBarRadioGroup, {
    IMenuBarRadioButton,
} from "@rich-editor/menuBar/paragraph/items/ParagraphMenuBarRadioGroup";
import classNames from "classnames";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";

interface IProps {
    items: IMenuBarRadioButton[];
    closeMenu: () => void;
    closeMenuAndSetCursor: () => void;
    className?: string;
    disabled?: boolean;
}

/**
 * Implemented tab content for special block styles
 */
export default class ParagraphMenuBlockTabContent extends React.Component<IProps> {
    public render() {
        const classes = richEditorClasses(false);
        const handleClick = (data: IMenuBarRadioButton, index: number) => {
            this.props.items[index].formatFunction();
            this.props.closeMenuAndSetCursor();
        };
        return (
            <ParagraphMenuBarRadioGroup
                className={classNames(classes.paragraphMenuPanel, this.props.className)}
                handleClick={handleClick}
                label={t("Special Formats")}
                items={this.props.items}
                disabled={this.props.disabled}
            />
        );
    }
}
