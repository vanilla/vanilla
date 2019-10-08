import React, { useState } from "react";
import classNames from "classnames";
import { TranslationGridRow } from "@library/content/translationGrid/TranslationGridRow";
import { translationGridClasses } from "@library/content/translationGrid/TranslationGridStyles";
import { TranslationGridText } from "@library/content/translationGrid/TranslationGridText";
import InputTextBlock from "@library/forms/InputTextBlock";
import { AlertIcon, EditIcon } from "@library/icons/common";
import cloneDeep from "lodash/cloneDeep";
import { t } from "@library/utility/appUtils";

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
}

/**
 * Translation UI
 * @param props
 * @constructor
 */

export function TranslationGrid(props: ITranslationGrid) {
    const { data, inScrollingContainer = false } = props;
    const classes = translationGridClasses();
    const count = data.length - 1;
    const [translations, setTranslations] = useState(data);
    const translationKey = "newTranslation";
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
                        <div className={classNames(classes.rightCell, classes.headerRight)}>Français</div>
                    </div>
                    <div className={classes.body}>{translationRows}</div>
                </div>
            </div>
        </>
    );
}
