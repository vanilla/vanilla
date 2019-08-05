/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import { getRequiredID, IOptionalComponentID } from "@library/utility/idUtils";
import { checkRadioClasses } from "@library/forms/checkRadioStyles";
import classNames from "classnames";

interface IProps extends IOptionalComponentID {
    id?: string;
    className?: string;
    checked?: boolean;
    disabled?: boolean;
    onChange?: any;
    label: string;
}

interface IState {
    id: string;
}

/**
 * A styled, accessible checkbox component.
 */
export default class RadioGroup extends React.Component<IProps, IState> {}
