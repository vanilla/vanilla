/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { find } from "lodash";
import { ButtonTypes } from "@library/forms/buttonStyles";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";

interface IState {
    id: string;
}

export interface ILanguageProps extends ISelectBoxItem {
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
}

/**
 * Implements "other languages" DropDown for articles.
 */
export default class LanguagesDropDown extends React.Component<ILanguageDropDownProps, IState> {
    public render() {
        const showPicker = this.props.data && this.props.data.length > 1;
        const fakeData = this.props.data.map(value => {
            value.name = value.locale;
            return value;
        });

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
                    label={!this.props.titleID ? t("Locale") : null}
                    widthOfParent={!!this.props.widthOfParent}
                    className={classNames("languagesDropDown", this.props.className)}
                    renderLeft={this.props.renderLeft}
                    buttonClassName={this.props.buttonClassName}
                    buttonBaseClass={this.props.buttonBaseClass}
                    openAsModal={this.props.openAsModal}
                >
                    {processedChildren}
                </SelectBox>
            );
        } else {
            return null;
        }
    }
}
