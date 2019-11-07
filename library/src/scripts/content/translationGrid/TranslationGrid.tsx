import React, { useState, useCallback, useEffect } from "react";
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
import {
    useLocaleInfo,
    LocaleDisplayer,
    ILocale,
    loadLocales,
    ITranslationProperty,
    TranslationPropertyType,
} from "@vanilla/i18n";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import { panelListClasses } from "@library/layout/panelListStyles";
import { TranslationProperty } from "@library/content/translationGrid/TranslationProperty";

interface ITranslations {
    [propertyKey: string]: string;
}

export interface ITranslationGrid {
    properties: ITranslationProperty[];
    existingTranslations: ITranslations;
    inScrollingContainer?: boolean;
    otherLanguages: ILanguageItem[];
    dateUpdated?: string;
}

function useTranslationState(initialTranslations: ITranslations) {
    const [inProgressTranslations, setInProgressTranslations] = useState(initialTranslations);
    useEffect(() => {
        setInProgressTranslations(initialTranslations);
    }, [initialTranslations]);

    const updateTranslationDraft = useCallback(
        (propertyKey: string, translation: string) => {
            setInProgressTranslations({
                ...inProgressTranslations,
                [propertyKey]: translation,
            });
        },
        [setInProgressTranslations, inProgressTranslations],
    );

    return { inProgressTranslations, updateTranslationDraft };
}

/**
 * Translation UI
 * @param props
 * @constructor
 */
export function TranslationGrid(props: ITranslationGrid) {
    const id = useUniqueID("articleOtherLanguages");
    const { existingTranslations } = props;
    const { inProgressTranslations, updateTranslationDraft } = useTranslationState(existingTranslations);

    const classesPanelList = panelListClasses();
    const { properties, inScrollingContainer = false, otherLanguages } = props;
    const classes = translationGridClasses();
    const currentLocale = "en";
    let selectedIndex = 0;
    const selectBoxItems: ISelectBoxItem[] = otherLanguages.map((data, index) => {
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

    return (
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
                <div className={classes.body}>
                    {properties.map((property, i) => (
                        <TranslationProperty
                            key={property.propertyKey}
                            isFirst={i === 0}
                            isLast={i === properties.length - 1}
                            property={property}
                            existingTranslation={existingTranslations[property.propertyKey] || null}
                            translationValue={inProgressTranslations[property.propertyKey]}
                            onTranslationChange={updateTranslationDraft}
                        />
                    ))}
                </div>
            </div>
        </div>
    );
}
