/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { richEditorClasses } from "@library/editor/richEditorStyles";
import { IconForButtonWrap } from "@library/editor/pieces/IconForButtonWrap";

interface IProps {
    name: string;
    icon: JSX.Element;
    isSelected: boolean;
    groupIndex: number;
    navigateToGroup(groupIndex: number);
}

export class EmojiGroupButton extends React.Component<IProps> {
    public render() {
        const classesRichEditor = richEditorClasses();
        const { name, icon, groupIndex, isSelected } = this.props;
        const buttonClasses = classNames(
            "richEditor-button",
            { isSelected },
            classesRichEditor.button,
            classesRichEditor.emojiGroup,
        );

        return (
            <button
                type="button"
                onClick={this.handleClick}
                aria-current={isSelected}
                aria-label={t("Jump to emoji category: ") + t(name)}
                title={t(name)}
                className={buttonClasses}
            >
                <IconForButtonWrap icon={icon} />
                <span className="sr-only">{t("Jump to emoji category: ") + t(name)}</span>
            </button>
        );
    }

    private handleClick = (event: React.MouseEvent) => {
        event.preventDefault();
        event.stopPropagation();
        this.props.navigateToGroup(this.props.groupIndex);
    };
}
