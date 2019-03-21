/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { paragraphMenuCheckRadioClasses } from "../paragraphMenuBarStyles";
import classNames from "classnames";
import { IMenuBarItemTypes } from "@rich-editor/menuBar/paragraph/ParagraphMenusBarToggle";

export interface IMenuCheckRadio {
    checked: boolean;
    icon: JSX.Element;
    text: string;
}

interface IProps {
    checked: boolean;
    icon: JSX.Element;
    text: string;
    type: IMenuBarItemTypes;
    handleClick: (event: any) => void;
}

/**
 * Implemented component for menu items that behave like checkboxes or radio buttons.
 * Both are quite similar, so they are made with the same component.
 * Note that the radio button shouldn't be rendered alone. It should be wrapped in a ParagraphMenuBarRadioGroup component
 */
export default class ParagraphMenuCheckRadio extends React.PureComponent<IProps> {
    public render() {
        const classes = paragraphMenuCheckRadioClasses();
        const { checked, type, icon, text, handleClick } = this.props;
        const isRadio = type === IMenuBarItemTypes.RADIO;
        return (
            <button
                className={classNames(
                    classes.checkRadio,
                    isRadio ? classes.radio : classes.check,
                    checked ? classes.checked : "",
                )}
                role={isRadio ? "menuitemradio" : "menuitemcheckbox"}
                aria-checked={checked}
                onClick={handleClick}
            >
                <span />
                {icon}
                {text}
            </button>
        );
    }
}
