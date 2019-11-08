import React, { useState, useEffect } from "react";
import classNames from "classnames";
import { TranslationGrid } from "@library/content/translationGrid/TranslationGrid";
import { ITranslationGrid } from "./TranslationGrid";
import { panelListClasses } from "@library/layout/panelListStyles";
import { translationGridClasses } from "@library/content/translationGrid/TranslationGridStyles";
import LanguagesDropDown from "@library/layout/LanguagesDropDown";
import { AlertIcon } from "@library/icons/common";
import { useUniqueID } from "@library/utility/idUtils";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { useLocaleInfo, LocaleDisplayer, ILocale, loadLocales } from "@vanilla/i18n";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import { ILanguageItem } from "@library/layout/LanguagesDropDown";

export interface ITranslation {
    id: string;
    source: string;
    translation: string;
    multiLine?: boolean; // We'll default to a textarea, that looks like a single line, but it would be much better to know.
    maxLength?: number; // Please add maximum character counts where possible.
}

export interface ITranslationDummy {
    // for storybook
    key: string;
    locale: string;
    source: string;
    translation: string;
    translationStatus: string;
    multiLine?: boolean;
    maxLength?: number;
}
export interface ITranslationLanguageHandler {
    inScrollingContainer?: boolean;
    i18nLocales: ILocale[];
    dateUpdated?: string;
    newTranslationData: ITranslationDummy[]; // for storybook
}

export function TranslationLanguageHandler(props: ITranslationLanguageHandler) {
    let { currentLocale, locales } = useLocaleInfo();
    locales = locales.filter(val => val.localeKey != currentLocale);
    const classesPanelList = panelListClasses();
    const id = useUniqueID("articleOtherLanguages");
    const classes = translationGridClasses();
    //let selectedIndex = 0;
    let { newTranslationData } = props;
    const [selectedItem, setSelectedItem] = useState<string | null>(locales ? locales[0].localeKey : null);
    let [selectedIndex, setSelectedIndex] = useState<number>(0);

    const handleChange = (name: string, index: number) => {
        setSelectedItem(name);
        setSelectedIndex(index);
        const filteredData = newTranslationData.filter(v => v.locale === name);
        setData(filteredData);
    };

    useEffect(() => {
        const filteredData = newTranslationData.filter(v => v.locale === locales[0].localeKey);
        setData(filteredData);
    }, [newTranslationData]);

    let [newData, setData] = useState(newTranslationData);

    const selectBoxItems: ISelectBoxItem[] = locales.map((data, index) => {
        const isSelected = true;
        if (isSelected) {
            selectedIndex = selectedIndex;
        }
        return {
            selected: isSelected,
            name: data.localeKey,
            icon: (
                <span tabIndex={0}>
                    <AlertIcon className={"selectBox-selectedIcon"} />
                </span>
            ),

            content: (
                <>
                    <LocaleDisplayer displayLocale={data.localeKey} localeContent={data.localeKey} />
                </>
            ),
        };
    });
    return (
        <TranslationGrid
            {...props}
            newTranslationData={newData}
            rightHeaderCell={
                <div className={classes.languageDropdown}>
                    <div className={classNames("otherLanguages", "panelList", classesPanelList.root)}>
                        <SelectBox
                            describedBy={id!}
                            widthOfParent={!!true}
                            className={classNames("languagesDropDown", "otherLanguages-select")}
                            renderLeft={true}
                            selectedIndex={selectedIndex}
                            handleChange={(val, i) => {
                                handleChange(val, i);
                            }}
                        >
                            {selectBoxItems}
                        </SelectBox>
                    </div>
                </div>
            }
            leftHeaderCell={
                <Translate
                    source="<0/> (Source)"
                    c0={
                        <>
                            <LocaleDisplayer displayLocale={currentLocale || ""} localeContent={currentLocale || ""} />
                        </>
                    }
                />
            }
        />
    );
}
