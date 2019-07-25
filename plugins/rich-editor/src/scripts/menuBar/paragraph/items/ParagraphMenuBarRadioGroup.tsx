/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import ParagraphMenuCheckRadio, {
    IMenuCheckRadio,
} from "@rich-editor/menuBar/paragraph/pieces/ParagraphMenuCheckRadio";
import { IMenuBarItemTypes } from "@rich-editor/menuBar/paragraph/ParagraphMenusBarToggle";
import { paragraphMenuCheckRadioClasses } from "@rich-editor/menuBar/paragraph/paragraphMenuBarStyles";

export interface IMenuBarRadioButton extends IMenuCheckRadio {
    formatFunction: () => void;
}

export interface IParagraphMenuBarRadioGroupProps {
    label: string;
    className?: string;
    items: IMenuBarRadioButton[];
    handleClick: (data: IMenuBarRadioButton, index: number) => void;
    disabled?: boolean;
}

/**
 * Implements group of menu elements that behave like a group of radio buttons.
 */
export default class ParagraphMenuBarRadioGroup extends React.PureComponent<IParagraphMenuBarRadioGroupProps> {
    public render() {
        const classes = paragraphMenuCheckRadioClasses();

        if (this.props.items && this.props.items.length > 0) {
            return (
                <div
                    aria-label={this.props.label}
                    role="group"
                    className={classNames(classes.group, this.props.className)}
                >
                    {this.props.items.map((item, index) => {
                        const onClick = (event: MouseEvent) => {
                            this.props.handleClick(item, index);
                        };
                        return (
                            <ParagraphMenuCheckRadio
                                checked={item.checked}
                                icon={item.icon}
                                text={item.text}
                                type={IMenuBarItemTypes.RADIO}
                                onClick={onClick}
                                disabled={item.disabled || !!this.props.disabled}
                                key={`checkRadio-${index}`}
                            />
                        );
                    })}
                </div>
            );
        } else {
            return null;
        }
    }
}
