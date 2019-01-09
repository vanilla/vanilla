/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/application";

interface IProps {
    name: string;
    icon: React.ReactNode;
    isSelected: boolean;
    groupIndex: number;
    navigateToGroup(groupIndex: number);
}

export class EmojiGroupButton extends React.Component<IProps> {
    public render() {
        const { name, icon, groupIndex, isSelected } = this.props;
        const buttonClasses = classNames("richEditor-button", "emojiGroup", { isSelected });

        return (
            <button
                type="button"
                onClick={this.handleClick}
                aria-current={isSelected}
                aria-label={t("Jump to emoji category: ") + t(name)}
                title={t(name)}
                className={buttonClasses}
            >
                {icon}
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
