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
interface IState {
    id: string;
}

export interface ILanguageItem {
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
    handleOnChange?: (...args: any[]) => any;
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
        const selcteBoxItems: ISelectBoxItem[] = this.props.selcteBoxItems;

        return (
            <SelectBox
                describedBy={this.props.titleID!}
                widthOfParent={!!this.props.widthOfParent}
                className={classNames("languagesDropDown", this.props.className)}
                openAsModal={this.props.openAsModal}
                selectedIndex={this.props.selectedIndex}
                handleOnChange={this.props.handleOnChange}
            >
                {this.props.selcteBoxItems}
            </SelectBox>
        );
    }
}
