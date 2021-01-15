/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { paragraphMenuCheckRadioClasses } from "../paragraphMenuBarStyles";
import classNames from "classnames";
import { IMenuBarItemTypes } from "@rich-editor/menuBar/paragraph/ParagraphMenusBarToggle";
import { style } from "@library/styles/styleShim";
import { visibility } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { CheckIcon } from "@library/icons/common";
import { t } from "@vanilla/i18n/src";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";

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
            width: styleUnit(globalStyles.icon.sizes.default),
            height: styleUnit(globalStyles.icon.sizes.default),
        });
        const checkStyle = style({
            color: ColorsUtils.colorOut(globalStyles.mainColors.primary),
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
                <span aria-hidden={"true"} className={classes.icon}>
                    {icon}
                </span>
                <span className={classes.checkRadioLabel}>{text}</span>
                {checked && (
                    <span className={classes.checkRadioSelected}>
                        {
                            <>
                                <ScreenReaderContent>
                                    {classes.checked ? t("Checked") : t("Unchecked")}
                                </ScreenReaderContent>
                                <CheckIcon aria-hidden={"true"} className={classNames(checkStyle, iconStyle)} />
                            </>
                        }
                    </span>
                )}
            </button>
        );
    }
}
