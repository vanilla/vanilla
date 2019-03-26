/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { paragraphMenuCheckRadioClasses } from "../paragraphMenuBarStyles";
import classNames from "classnames";
import { IMenuBarItemTypes } from "@rich-editor/menuBar/paragraph/ParagraphMenusBarToggle";
import { check } from "@library/icons/common";

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
    onClick: (event: any) => void;
}

/**
 * Implemented component for menu items that behave like checkboxes or radio buttons.
 * Both are quite similar, so they are made with the same component.
 * Note that the radio button shouldn't be rendered alone. It should be wrapped in a ParagraphMenuBarRadioGroup component
 */
export default class ParagraphMenuCheckRadio extends React.PureComponent<IProps> {
    public render() {
        const classes = paragraphMenuCheckRadioClasses();
        const { checked, type, icon, text, onClick } = this.props;
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
                type="button"
                onClick={onClick}
            >
                <span className={classes.icon}>{icon}</span>
                <span className={classes.checkRadioLabel}>{text}</span>
                {checked && <span className={classes.checkRadioSelected}>{check()}</span>}
            </button>
        );
    }
}
