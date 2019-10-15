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

export interface ILanguageProps extends ISelectBoxItem {
    [x: string]: any;
    lang: string;
    outdated?: boolean;
}

export interface ILanguageDropDownProps {
    id?: string;
    data: ILanguageProps[];
    titleID?: string; // set when it comes with a heading
    widthOfParent?: boolean;
    selected: any;
    className?: string;
    buttonClassName?: string;
    buttonBaseClass?: ButtonTypes;
    renderLeft?: boolean;
    openAsModal?: boolean;
    localeInfo?: ILocale[];
    currentLocale?: any;
    languageSelect?: boolean;
}

/**
 * Implements "other languages" DropDown for articles.
 */
export default class LanguagesDropDown extends React.Component<ILanguageDropDownProps, IState> {
    public render() {
        const showPicker = this.props.data && this.props.data.length > 1;
        if (showPicker) {
            let foundIndex = false;
            const processedChildren = this.props.data.map(language => {
                const selected = language.locale === this.props.selected;
                language.selected = selected;
                if (selected) {
                    foundIndex = selected;
                }
                return language;
            });
            if (!foundIndex) {
                processedChildren[0].selected = true;
            }
            return (
                <SelectBox
                    describedBy={this.props.titleID!}
                    //label={!this.props.titleID ? t("Locale") : null}
                    widthOfParent={!!this.props.widthOfParent}
                    className={classNames("languagesDropDown", this.props.className)}
                    renderLeft={this.props.renderLeft}
                    buttonClassName={this.props.buttonClassName}
                    buttonBaseClass={this.props.buttonBaseClass}
                    openAsModal={this.props.openAsModal}
                    localeInfo={this.props.localeInfo}
                    currentLocale={this.props.currentLocale}
                    languageSelect={true}
                >
                    {processedChildren}
                </SelectBox>
            );
        } else {
            return null;
        }
    }
}
