/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { panelListClasses } from "@library/layout/panelListStyles";
import { ITranslationProperty, LocaleDisplayer, getLocales } from "@vanilla/i18n";
import classNames from "classnames";
import React, { useCallback, useState } from "react";
import { translationGridClasses } from "./TranslationGridStyles";
import { TranslationProperty } from "./TranslationProperty";
import { TranslationGridLocaleChooser } from "./TranslationGridLocaleChooser";
import Translate from "@library/content/Translate";
import { makeTranslationKey } from "../translator/TranslationActions";
import { useSection } from "@library/layout/LayoutContext";

export interface ITranslations {
    [propertyKey: string]: string;
}

type TranslationUpdater = (key: string, value: string) => void;

export interface ITranslationGrid {
    properties: ITranslationProperty[];
    existingTranslations: ITranslations;
    onTranslationUpdate: TranslationUpdater;
    inScrollingContainer?: boolean;
    onActiveLocaleChange?: (newLocale: string | null) => void;
    activeLocale: string | null;
    sourceLocale: string;
}

export function TranslationGrid(props: ITranslationGrid) {
    const { existingTranslations, onTranslationUpdate } = props;
    const { inProgressTranslations, updateTranslationDraft } = useTranslationState(
        existingTranslations,
        onTranslationUpdate,
    );
    const classesPanelList = panelListClasses(useSection().mediaQueries);
    const { properties, inScrollingContainer = false } = props;

    const classes = translationGridClasses();

    return (
        <div className={classNames(classes.root, { [classes.inScrollContainer]: inScrollingContainer })}>
            <div className={classes.frame}>
                <div className={classes.header}>
                    <div className={classNames(classes.leftCell, classes.headerLeft)}>
                        <Translate
                            source="<0/> (Source)"
                            c0={
                                <LocaleDisplayer
                                    displayLocale={props.sourceLocale}
                                    localeContent={props.sourceLocale}
                                />
                            }
                        />
                    </div>
                    <div className={classNames(classes.rightCell, classes.headerRight)}>
                        <div className={classes.languageDropdown}>
                            <div className={classNames("otherLanguages", "panelList", classesPanelList.root)}>
                                <TranslationGridLocaleChooser
                                    sourceLocale={props.sourceLocale}
                                    selectedLocale={props.activeLocale}
                                    onChange={(locale) =>
                                        props.onActiveLocaleChange && props.onActiveLocaleChange(locale)
                                    }
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div className={classes.body}>
                    {properties.map((property, i) => {
                        const propertyKey = makeTranslationKey(property);
                        return (
                            <TranslationProperty
                                key={propertyKey}
                                isFirst={i === 0}
                                isLast={i === properties.length - 1}
                                property={property}
                                existingTranslation={existingTranslations[propertyKey] || null}
                                translationValue={inProgressTranslations[propertyKey]}
                                onTranslationChange={updateTranslationDraft}
                            />
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

function useTranslationState(initialTranslations: ITranslations, afterSelfUpdate?: TranslationUpdater) {
    const [inProgressTranslations, setInProgressTranslations] = useState(initialTranslations);

    const updateTranslationDraft = useCallback(
        (propertyKey: string, translation: string) => {
            setInProgressTranslations({
                ...inProgressTranslations,
                [propertyKey]: translation,
            });
            afterSelfUpdate && afterSelfUpdate(propertyKey, translation);
        },
        [setInProgressTranslations, inProgressTranslations],
    );

    return { inProgressTranslations, updateTranslationDraft };
}
