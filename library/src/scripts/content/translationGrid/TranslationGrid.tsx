import React from "react";
import { IUser, IUserRoles } from "@library/@types/api/users";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { metasClasses } from "@library/styles/metasStyles";
import { rolesClasses } from "@library/content/rolesStyles";
import classNames from "classnames";
import { TranslationGridRow } from "@library/content/translationGrid/TranslationGridRow";
import { translationGridClasses } from "@library/content/translationGrid/TranslationGridStyles";
import { TranslationGridText } from "@library/content/translationGrid/TranslationGridText";
import InputTextBlock from "@library/forms/InputTextBlock";

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

    const classesMeta = metasClasses();
    const classes = translationGridClasses();
    const count = props.data.length - 1;
    const translationRows = data.map((t, i) => {
        return (
            <TranslationGridRow
                key={`translationGridRow-${i}`}
                isFirst={i === 0}
                isLast={i === count}
                leftCell={<TranslationGridText text={t.source} />}
                rightCell={
                    <InputTextBlock
                        wrapClassName={classes.inputWrapper}
                        inputProps={{
                            inputClassNames: classes.input,
                            defaultValue: t.translation,
                            multiline: !!t.multiLine,
                            maxLength: t.maxLength,
                        }}
                        textAreaProps={{
                            resize: "none",
                            async: true,
                        }}
                    />
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
