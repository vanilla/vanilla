/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { ITranslationProperty, TranslationPropertyType, t } from "@vanilla/i18n";
import { translationGridClasses } from "@library/content/translationGrid/TranslationGridStyles";
import { TranslationGridRow } from "@library/content/translationGrid/TranslationGridRow";
import { TranslationGridText } from "@library/content/translationGrid/TranslationGridText";
import { EditIcon, AlertIcon } from "@library/icons/common";
import classNames from "classnames";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import InputTextBlock from "@library/forms/InputTextBlock";

export function TranslationProperty(props: {
    property: ITranslationProperty;
    translationValue: string;
    onTranslationChange: (propertyKey: string, newValue: string) => void;
    existingTranslation: string | null;
    isFirst?: boolean;
    isLast?: boolean;
}) {
    const { existingTranslation, property, translationValue, isFirst, isLast } = props;
    const isEditing = existingTranslation && translationValue !== existingTranslation;
    const isMultiLine = property.propertyType === TranslationPropertyType.TEXT_MULTILINE;

    const classes = translationGridClasses();

    return (
        <TranslationGridRow
            key={property.propertyKey}
            isFirst={isFirst}
            leftCell={<TranslationGridText text={property.sourceText} />}
            rightCell={
                <>
                    {isEditing && (
                        <EditIcon
                            className={classNames(classes.icon, { [classes.isFirst]: isFirst })}
                            title={t("You have unsaved changes")}
                        />
                    )}
                    {!existingTranslation && (
                        <ToolTip label={t("Not translated")} ariaLabel={t("Not Translated")}>
                            <ToolTipIcon>
                                <AlertIcon className={classNames(classes.icon, { [classes.isFirst]: isFirst })} />
                            </ToolTipIcon>
                        </ToolTip>
                    )}
                    <InputTextBlock
                        className={classNames({
                            [classes.fullHeight]: isMultiLine || isLast,
                        })}
                        wrapClassName={classNames(classes.inputWrapper, {
                            [classes.fullHeight]: isMultiLine || isLast,
                        })}
                        inputProps={{
                            inputClassNames: classNames(classes.input, { [classes.fullHeight]: isLast }),
                            onChange: (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
                                const { value } = event.target;
                                props.onTranslationChange(property.propertyKey, value);
                            },
                            value: translationValue != null ? translationValue : property.sourceText,
                            multiline: isMultiLine,
                            maxLength: property.propertyValidation.maxLength,
                        }}
                        multiLineProps={{
                            resize: "none",
                            async: true,
                            className: classes.multiLine,
                            rows: 3,
                        }}
                    />
                </>
            }
        />
    );
}
