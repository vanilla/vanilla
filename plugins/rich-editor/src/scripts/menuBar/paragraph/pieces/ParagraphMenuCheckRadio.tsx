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
import { style } from "typestyle";
import { colorOut, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";

export interface IMenuCheckRadio {
    checked: boolean;
    icon: JSX.Element;
    text: string;
    disabled?: boolean;
}

interface IProps {
    checked: boolean;
    icon: JSX.Element;
    text: string;
    type: IMenuBarItemTypes;
    onClick: (event: any) => void;
    disabled?: boolean;
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
        const globalStyles = globalVariables();
        const iconStyle = style({
            width: unit(globalStyles.icon.sizes.default),
            height: unit(globalStyles.icon.sizes.default),
        });
        const checkStyle = style({
            color: colorOut(globalStyles.mainColors.primary),
        });
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
                disabled={this.props.disabled}
                tabIndex={this.props.disabled ? -1 : 0}
                data-firstletter={text.toLowerCase().substr(0, 1)}
            >
                <span className={classes.icon}>{icon}</span>
                <span className={classes.checkRadioLabel}>{text}</span>
                {checked && (
                    <span className={classes.checkRadioSelected}>{check(classNames(checkStyle, iconStyle))}</span>
                )}
            </button>
        );
    }
}
