import React, { useState } from "react";
import classNames from "classnames";
import { PanelWidget } from "@library/layout/PanelLayout";
import { TranslationGridRow } from "@library/content/translationGrid/TranslationGridRow";
import { translationGridClasses } from "@library/content/translationGrid/TranslationGridStyles";
import { TranslationGridText } from "@library/content/translationGrid/TranslationGridText";
import InputTextBlock from "@library/forms/InputTextBlock";
import { AlertIcon, EditIcon } from "@library/icons/common";
import cloneDeep from "lodash/cloneDeep";
import { t } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import LanguagesDropDown from "@library/layout/LanguagesDropDown";
import { ILanguageItem } from "@library/layout/LanguagesDropDown";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { useLocaleInfo, LocaleDisplayer, ILocale, loadLocales } from "@vanilla/i18n";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import { panelListClasses } from "@library/layout/panelListStyles";

export interface ITranslation {
    id: string;
    source: string;
    translation: string;
    multiLine?: boolean; // We'll default to a textarea, that looks like a single line, but it would be much better to know.
    maxLength?: number; // Please add maximum character counts where possible.
}

export interface ITranslationGrid {
    data: ITranslation[];
    inScrollingContainer?: boolean;
    otherLanguages: ILanguageItem[];
    i18nLocales: ILocale[];
    dateUpdated?: string;
}

/**
 * Translation UI
 * @param props
 * @constructor
 */

export function TranslationGrid(props: ITranslationGrid) {
    const id = useUniqueID("articleOtherLanguages");
    const classesPanelList = panelListClasses();
    const { data, inScrollingContainer = false, otherLanguages } = props;
    const dateUpdated = "2019-10-08T13:54:41+00:00";
    const classes = translationGridClasses();
    const count = data.length - 1;
    const [translations, setTranslations] = useState(data);
    const translationKey = "newTranslation";
    const currentLocale = "en";
    let selectedIndex = 0;
    const selectBoxItems: ISelectBoxItem[] = props.otherLanguages.map((data, index) => {
        const isSelected = data.locale === currentLocale;
        if (isSelected) {
            selectedIndex = index;
        }
        return {
            selected: isSelected,
            name: data.locale,
            icon: data.translationStatus === "not-translated" && (
                <ToolTip
                    label={
                        <Translate
                            source="This article was edited in source locale on <0/>. Edit this article to update its translation and clear this message."
                            c0={<DateTime timestamp={props.dateUpdated} />}
                        />
                    }
                    ariaLabel={"This article was editied in its source locale."}
                >
                    <span tabIndex={0}>
                        <AlertIcon className={"selectBox-selectedIcon"} />
                    </span>
                </ToolTip>
            ),
            content: (
                <>
                    <LocaleDisplayer displayLocale={data.locale} localeContent={data.locale} />
                </>
            ),
            onClick: () => {
                window.location.href = data.url;
            },
        };
    });

    const translationRows = translations.map((translation, i) => {
        const notTranslated = !translations[i][translationKey];
        const newTranslation = translations[i][translationKey] || "";
        const isEditing = newTranslation !== "" && newTranslation !== translation.translation;
        const isFirst = i === 0;
        const isLast = i === count;

        return (
            <TranslationGridRow
                key={`translationGridRow-${i}`}
                isFirst={isFirst}
                isLast={isLast}
                leftCell={<TranslationGridText text={translation.source} />}
                rightCell={
                    <>
                        {isEditing && (
                            <EditIcon
                                className={classNames(classes.icon, { [classes.isFirst]: isFirst })}
                                title={t("You have unsaved changes")}
                            />
                        )}
                        {notTranslated && (
                            <AlertIcon
                                className={classNames(classes.icon, { [classes.isFirst]: isFirst })}
                                title={t("Not translated")}
                            />
                        )}
                        <InputTextBlock
                            className={classNames({ [classes.fullHeight]: translation.multiLine || isLast })}
                            wrapClassName={classNames(classes.inputWrapper, {
                                [classes.fullHeight]: translation.multiLine || isLast,
                            })}
                            inputProps={{
                                inputClassNames: classNames(classes.input, { [classes.fullHeight]: isLast }),
                                onChange: (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
                                    const { value } = event.target;
                                    translations[i][translationKey] = value;
                                    setTranslations(cloneDeep(translations));
                                },
                                value: notTranslated ? translation.translation : newTranslation,
                                multiline: translation.multiLine,
                                maxLength: translation.maxLength,
                            }}
                            multiLineProps={{
                                resize: "none",
                                async: true,
                                className: classes.multiLine,
                            }}
                        />
                    </>
                }
            />
        );
    });

    return (
        <>
            <div className={classNames(classes.root, { [classes.inScrollContainer]: inScrollingContainer })}>
                <div className={classes.frame}>
                    <div className={classes.header}>
                        <div className={classNames(classes.leftCell, classes.headerLeft)}>English (source)</div>
                        <div className={classNames(classes.rightCell, classes.headerRight)}>
                            <div className={classes.languageDropdown}>
                                <div className={classNames("otherLanguages", "panelList", classesPanelList.root)}>
                                    <LanguagesDropDown
                                        titleID={id}
                                        widthOfParent={true}
                                        className="otherLanguages-select"
                                        renderLeft={true}
                                        data={props.otherLanguages}
                                        currentLocale={currentLocale}
                                        dateUpdated={props.dateUpdated}
                                        selcteBoxItems={selectBoxItems}
                                        selectedIndex={selectedIndex}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className={classes.body}>{translationRows}</div>
                </div>
            </div>
        </>
    );
}
