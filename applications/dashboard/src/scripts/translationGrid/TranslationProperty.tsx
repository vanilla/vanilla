/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { ITranslationProperty, TranslationPropertyType, t } from "@vanilla/i18n";
import { translationGridClasses } from "./TranslationGridStyles";
import { TranslationGridRow } from "./TranslationGridRow";
import { TranslationGridText } from "./TranslationGridText";
import { EditIcon } from "@library/icons/common";
import classNames from "classnames";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import InputTextBlock from "@library/forms/InputTextBlock";
import { makeTranslationKey } from "../translator/TranslationActions";
import { iconClasses } from "@library/icons/iconStyles";
import { cx } from "@emotion/css";
import { Icon } from "@vanilla/icons";

interface IProps {
    /** The translation property to represent */
    property: ITranslationProperty;

    /**
     * The current translated value for the property
     * This is NOT saved anywhere & represents a WIP draft state.
     **/
    translationValue: string;

    /**
     * Callback for when the tranlsation draft state changes.
     */
    onTranslationChange: (propertyKey: string, newValue: string) => void;

    /**
     * An existing translation for the row if it exists.
     * This is used to initiliaze the content of the row.
     */
    existingTranslation: string | null;

    /** For UI alignment. */
    isFirst?: boolean;

    /** For UI alignment. */
    isLast?: boolean;
}

/**
 * Wired up data component to represent a translation property in a grid row.
 *
 * Using memo for performance reasons (when the grid update we don't want to re-render every row).
 */
export const TranslationProperty = React.memo(function TranslationProperty(props: IProps) {
    const { existingTranslation, property, translationValue, isFirst, isLast } = props;
    let isEditing = false;
    if (existingTranslation) {
        isEditing = existingTranslation !== translationValue;
    } else {
        isEditing = !!translationValue;
    }

    const isMultiLine = property.propertyType === TranslationPropertyType.TEXT_MULTILINE;

    const classes = translationGridClasses();
    const properyKey = makeTranslationKey(property);

    return (
        <TranslationGridRow
            key={properyKey}
            isFirst={isFirst}
            isLast={isLast}
            leftCell={<TranslationGridText text={property.sourceText} />}
            rightCell={
                <>
                    {isEditing && (
                        <EditIcon
                            className={classNames(classes.icon, classes.editIcon, { [classes.isFirst]: isFirst })}
                            title={t("You have unsaved changes")}
                            small={true}
                        />
                    )}
                    {!isEditing && !existingTranslation && (
                        <ToolTip label={t("Not translated")} ariaLabel={t("Not Translated")}>
                            <ToolTipIcon>
                                <Icon
                                    className={cx(classes.icon, iconClasses().errorFgColor, !!isFirst && "isFirst")}
                                    icon={"status-warning"}
                                    size={"compact"}
                                />
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
                                props.onTranslationChange(properyKey, value);
                            },
                            value: translationValue,
                            placeholder: property.sourceText,
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
});
