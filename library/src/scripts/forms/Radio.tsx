/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import {getRequiredID, IOptionalComponentID, useUniqueID} from "@library/utility/idUtils";
import { checkRadioClasses } from "@library/forms/checkRadioStyles";
import classNames from "classnames";

interface IProps extends IOptionalComponentID {
    id?: string;
    className?: string;
    checked?: boolean;
    disabled?: boolean;
    onChange?: any;
    label: string;
    radioGroup: string;
    note?: string;
    externalLabel: boolean;
}

interface IState {
    id: string;
}

/**
 * A styled, accessible checkbox component.
 */
export default function Checkbox(props) {
    const labelID = useUniqueID("radioButton-label");
    const classes = checkRadioClasses();
    const Tag = props.externalLabel ? "div" : "label";
    return {
        return (
            {/*<Tag className="radioButton">*/}
            {/*    <input*/}
            {/*        className="radioButton-input"*/}
            {/*        onChange={props.onChange}*/}
            {/*        aria-disabled={props.disabled}*/}
            {/*        radioGroup={props.radioGroup}*/}
            {/*        disabled={props.disabled}*/}
            {/*        type="radio"*/}
            {/*    />*/}
            {/*    <span className="radioButton-disk">*/}
            {/*        <span className="radioButton-state">*/}
            {/*            <svg className="radioButton-icon radioButton-diskIcon">*/}
            {/*                <title>{t("Radio Button")}</title>*/}
            {/*                <circle fill="currentColor" cx="3" cy="3" r="3" />*/}
            {/*            </svg>*/}
            {/*        </span>*/}
            {/*    </span>*/}
            {/*    {props.note && <span className="radioButton-labelNote">{props.note}</span>}}*/}
            {/*</Tag>*/}
        );
    }
}
