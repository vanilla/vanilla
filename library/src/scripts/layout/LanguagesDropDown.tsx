/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { ButtonTypes } from "@library/forms/buttonStyles";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { useLocaleInfo, LocaleDisplayer, ILocale } from "@vanilla/i18n";
import { ILoadable, LoadStatus } from "@library/@types/api/core";

interface IState {
    id: string;
}

interface ILanguageItem {
    locale: string;
    url: string;
    translationStatus: string;
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
}

/**
 * Implements "other languages" DropDown for articles.
 */
export default class LanguagesDropDown extends React.Component<ILanguageDropDownProps, IState> {
    public render() {
        const showPicker = this.props.data && this.props.data.length > 1;
        if (!showPicker) {
            return null;
        }

        let selectedIndex = 0;
        const selectBoxItems: ISelectBoxItem[] = this.props.data.map((data, index) => {
            const isSelected = data.locale === this.props.currentLocale;
            if (isSelected) {
                selectedIndex = index;
            }
            return {
                selected: isSelected,
                name: data.locale,
                content: <LocaleDisplayer displayLocale={data.locale} localeContent={data.locale} />,
                onClick: () => {
                    window.location.href = data.url;
                },
                translationStatus: data.translationStatus,
            };
        });

        return (
            <SelectBox
                describedBy={this.props.titleID!}
                widthOfParent={!!this.props.widthOfParent}
                className={classNames("languagesDropDown", this.props.className)}
                buttonClassName={this.props.buttonClassName}
                buttonBaseClass={this.props.buttonBaseClass}
                openAsModal={this.props.openAsModal}
                selectedIndex={selectedIndex}
            >
                {selectBoxItems}
            </SelectBox>
        );
    }
}
