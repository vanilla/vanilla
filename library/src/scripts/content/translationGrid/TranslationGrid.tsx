import React, { useState } from "react";
import { IUser, IUserRoles } from "@library/@types/api/users";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { metasClasses } from "@library/styles/metasStyles";
import { rolesClasses } from "@library/content/rolesStyles";
import classNames from "classnames";
import { TranslationGridRow } from "@library/content/translationGrid/TranslationGridRow";
import { translationGridClasses } from "@library/content/translationGrid/TranslationGridStyles";
import { TranslationGridText } from "@library/content/translationGrid/TranslationGridText";
import InputTextBlock from "@library/forms/InputTextBlock";
import { AlertIcon, EditIcon } from "@library/icons/common";
import cloneDeep from "lodash/cloneDeep";

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
    const translationRows = translations.map((t, i) => {
        const newTranslation = translations[i][translationKey] || "";
        const isEditing = newTranslation !== "" && newTranslation !== t.translation;
        const notTranslated = !isEditing && t.translation === "";

        return (
            <TranslationGridRow
                key={`translationGridRow-${i}`}
                isFirst={i === 0}
                isLast={i === count}
                leftCell={<TranslationGridText text={t.source} />}
                rightCell={
                    <>
                        {isEditing && <EditIcon className={classes.icon} />}
                        {notTranslated && <AlertIcon className={classes.icon} />}
                        <InputTextBlock
                            className={classNames({ [classes.fullHeight]: t.multiLine })}
                            wrapClassName={classNames(classes.inputWrapper, { [classes.fullHeight]: t.multiLine })}
                            inputProps={{
                                inputClassNames: classes.input,
                                onChange: (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
                                    const { value } = event.target;
                                    translations[i][translationKey] = value;
                                    setTranslations(cloneDeep(translations));
                                },
                                value: newTranslation !== "" ? newTranslation : t.translation,
                                multiline: t.multiLine,
                                maxLength: t.maxLength,
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
