/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classnames from "classnames";
import { richEditorClasses } from "@library/editor/richEditorStyles";
import { IconForButtonWrap } from "@library/editor/pieces/IconForButtonWrap";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";

export interface IMenuItemData {
    icon: JSX.Element;
    label: string;
    isActive: boolean;
    isDisabled?: boolean;
    onClick: (event: React.MouseEvent<HTMLButtonElement>) => void;
    onlyIcon?: boolean;
}

export interface IProps extends IMenuItemData {
    role: "menuitem" | "menuitemradio";
    focusNextItem: () => void;
    focusPrevItem: () => void;
    legacyMode: boolean;
}

/**
 * A component that when used with MenuItems provides an accessible WCAG compliant Menu implementation.
 *
 * @see https://www.w3.org/TR/wai-aria-practices-1.1/#menu
 */
export default class MenuItem extends React.PureComponent<IProps> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    public render() {
        const classesRichEditor = richEditorClasses(this.props.legacyMode);
        const { label, isDisabled, isActive, onClick, icon, role, onlyIcon } = this.props;
        const buttonClasses = classnames(
            "richEditor-button",
            "richEditor-formatButton",
            "richEditor-menuItem",
            classesRichEditor.button,
            classesRichEditor.menuItem,
            {
                isActive,
            },
        );

        const ariaAttributes = {
            role,
            "aria-label": label,
        };

        if (role === "menuitem") {
            ariaAttributes["aria-pressed"] = isActive;
        } else {
            ariaAttributes["aria-checked"] = isActive;
        }

        return (
            <button
                {...ariaAttributes}
                className={buttonClasses}
                type="button"
                onClick={this.onClick}
                onKeyDown={this.handleKeyPress}
                ref={this.buttonRef}
            >
                {onlyIcon && label && <ScreenReaderContent>{label}</ScreenReaderContent>}
                <IconForButtonWrap icon={icon} />
            </button>
        );
    }

    /**
     * Focus the button inside of this MenuItem.
     */
    public focus() {
        this.buttonRef.current && this.buttonRef.current.focus();
    }

    private onClick = (event: React.MouseEvent<HTMLButtonElement>) => {
        if (this.props.isDisabled) {
            event.preventDefault();
        } else {
            this.props.onClick(event);
        }
    };

    /**
     * Implement arrow keyboard shortcuts in accordance with the WAI-ARIA best practices for menuitems.
     *
     * @see https://www.w3.org/TR/wai-aria-practices/examples/menubar/menubar-2/menubar-2.html
     */
    private handleKeyPress = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
            case "ArrowRight":
            case "ArrowDown":
                event.stopPropagation();
                event.preventDefault();
                this.props.focusNextItem();
                break;
            case "ArrowUp":
            case "ArrowLeft":
                event.stopPropagation();
                event.preventDefault();
                this.props.focusPrevItem();
                break;
        }
    };
}
