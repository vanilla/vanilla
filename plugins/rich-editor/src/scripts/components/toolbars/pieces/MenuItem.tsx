/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import classnames from "classnames";
import { t } from "@dashboard/application";
import * as Icons from "@rich-editor/components/icons";

type FormatterCallback = (IMenuItemData) => void;

export interface IMenuItemData {
    active: boolean;
    label?: string;
    formatName?: string;
    formatter?: FormatterCallback;
    enableValue?: object;
    isFallback?: boolean;
}

interface IProps {
    propertyName: string;
    label: string;
    isActive: boolean;
    isFirst: boolean;
    isLast: boolean;
    onClick: (event: React.MouseEvent<any>) => void;
    onBlur?: (event?: React.FocusEvent<any>) => void;
    role?: string;
    disabled: boolean;
}

/**
 * Component for a single item in a EditorToolbar.
 */
export default class MenuItem extends React.Component<IProps> {
    private onBlur: (event?: React.FocusEvent<any>) => void;
    private buttonRole: string;
    private domButton: HTMLElement;

    constructor(props) {
        super(props);
        this.onBlur = props.isLast && props.onBlur ? props.onBlur : () => undefined;
        this.buttonRole = props.role || "button";
    }

    public render() {
        const { propertyName, isActive, onClick } = this.props;
        const Icon = Icons[propertyName];
        const buttonClasses = classnames("richEditor-button", "richEditor-formatButton", "richEditor-menuItem", {
            isActive,
        });

        return (
            <button
                ref={(ref: HTMLButtonElement) => {
                    this.domButton = ref;
                }}
                className={buttonClasses}
                type="button"
                aria-label={t("richEditor.menu." + this.props.propertyName)}
                role={this.props.role}
                aria-pressed={this.props.isActive}
                onClick={onClick}
                onBlur={this.onBlur}
                onKeyDown={this.handleKeyPress}
                disabled={this.props.disabled}
            >
                <Icon />
            </button>
        );
    }

    /**
     * Handle key presses
     */
    private handleKeyPress = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
            case "ArrowRight":
            case "ArrowDown":
                event.stopPropagation();
                event.preventDefault();
                if (this.props.isLast) {
                    const firstSibling = this.domButton.parentElement!.firstChild;
                    if (firstSibling instanceof HTMLElement) {
                        firstSibling.focus();
                    }
                } else {
                    const nextSibling = this.domButton.nextSibling;
                    if (nextSibling instanceof HTMLElement) {
                        nextSibling.focus();
                    }
                }
                break;
            case "ArrowUp":
            case "ArrowLeft":
                event.stopPropagation();
                event.preventDefault();
                if (this.props.isFirst) {
                    const lastSibling = this.domButton.parentElement!.lastChild;
                    if (lastSibling instanceof HTMLElement) {
                        lastSibling.focus();
                    }
                } else {
                    const previousSibling = this.domButton.previousSibling;
                    if (previousSibling instanceof HTMLElement) {
                        previousSibling.focus();
                    }
                }
                break;
        }
    };
}
