/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { selectBoxClasses } from "@library/forms/select/selectBoxStyles";
import { t } from "@library/utility/appUtils";
import { ButtonTypes } from "@library/forms/buttonStyles";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { useLocaleInfo, LocaleDisplayer, ILocale, loadLocales } from "@vanilla/i18n";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { AlertIcon } from "@library/icons/common";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import NumberType from "@storybook/addon-knobs/dist/components/types/Number";
interface IState {
    id: string;
}

interface ILanguageItem {
    locale: string;
    url: string;
    translationStatus: string;
    dateUpdated?: string;
}

export interface ILanguageDropDownProps {
    id?: string;
    data: ILanguageItem[];
    titleID?: string; // set when it comes with a heading
    widthOfParent?: boolean;
    className?: string;
    buttonClassName?: string;
    buttonBaseClass?: ButtonTypes;
    renderLeft?: boolean;
    openAsModal?: boolean;
    currentLocale?: string;
    selectedIndex?: number;
    selcteBoxItems: ISelectBoxItem[];
    dateUpdated?: string;
}

/**
 * Implements "other languages" DropDown for articles.
 */

export default class LanguagesDropDown extends React.Component<ILanguageDropDownProps, IState> {
    public render() {
        const classes = selectBoxClasses();
        const showPicker = this.props.data && this.props.data.length > 1;

        if (!showPicker) {
            return null;
        }

        return (
            <SelectBox
                describedBy={this.props.titleID!}
                widthOfParent={!!this.props.widthOfParent}
                className={classNames("languagesDropDown", this.props.className)}
                buttonClassName={this.props.buttonClassName}
                buttonBaseClass={this.props.buttonBaseClass}
                openAsModal={this.props.openAsModal}
                selectedIndex={this.props.selectedIndex}
            >
                {this.props.selcteBoxItems}
            </SelectBox>
        );
    }
}
