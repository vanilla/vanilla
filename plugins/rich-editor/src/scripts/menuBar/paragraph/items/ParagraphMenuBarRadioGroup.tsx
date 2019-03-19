/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { IMenuCheckRadio } from "@rich-editor/menuBar/paragraph/tabs/ParagraphMenuHeadingsTabContent";
import ParagraphMenuCheckRadio from "@rich-editor/menuBar/paragraph/pieces/ParagraphMenuCheckRadio";
import { IMenuBarItemTypes } from "@rich-editor/menuBar/paragraph/ParagraphMenusBarToggle";
import { paragraphMenuCheckRadioClasses } from "@rich-editor/menuBar/paragraph/paragraphMenuBarStyles";

export interface IMenuBarRadioButton extends IMenuCheckRadio {
    formatFunction: () => void;
}

export interface IParagraphMenuBarRadioGroupProps {
    label: string;
    classNames?: string;
    items: IMenuBarRadioButton[];
    activeIndex: number | null;
    handleClick: (data: IMenuBarRadioButton, index: number) => void;
}

/**
 * Implements group of menu elements that behave like a group of radio buttons.
 */
export default class ParagraphMenuBarRadioGroup extends React.PureComponent<IParagraphMenuBarRadioGroupProps> {
    public render() {
        const classes = paragraphMenuCheckRadioClasses();
        return (
            <div
                aria-label={this.props.label}
                role="group"
                className={classNames(classes.group, this.props.classNames)}
            >
                {this.props.items.map((item, index) => {
                    const onClick = (event: MouseEvent) => {
                        this.props.handleClick(item, index);
                    };
                    return (
                        <ParagraphMenuCheckRadio
                            checked={this.props.activeIndex === index}
                            icon={item.icon}
                            text={item.text}
                            type={IMenuBarItemTypes.RADIO}
                            handleClick={onClick}
                            key={`checkRadio-${index}`}
                        />
                    );
                })}
            </div>
        );
    }
}
