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
import { ITranslationLanguageHandler } from "./TranslationLanguageHandler";

export interface ITranslationGrid extends ITranslationLanguageHandler {
    rightHeaderCell: React.ReactNode;
    leftHeaderCell: React.ReactNode;
}

/**
 * Translation UI
 * @param props
 * @constructor
 */

export function TranslationGrid(props: ITranslationGrid) {
    const id = useUniqueID("articleOtherLanguages");
    const classesPanelList = panelListClasses();
    const { data, inScrollingContainer = false, otherLanguages, newTranslationData } = props;

    const classes = translationGridClasses();
    const count = data.length - 1;
    const [translations, setTranslations] = useState(newTranslationData);
    const translationKey = "newTranslation";

    const translationRows = newTranslationData.map((translation, i) => {
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
                        <div className={classNames(classes.leftCell, classes.headerLeft)}>{props.leftHeaderCell}</div>
                        <div className={classNames(classes.rightCell, classes.headerRight)}>
                            {props.rightHeaderCell}
                        </div>
                    </div>
                    <div className={classes.body}>{translationRows}</div>
                </div>
            </div>
        </>
    );
}
