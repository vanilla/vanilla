/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { PostField, PostType } from "@dashboard/postTypes/postType.types";
import { getIconForPostType } from "@dashboard/postTypes/utils";
import { cx } from "@emotion/css";
import { postSettingsFormClasses } from "@library/features/discussions/forms/PostSettings.classes";
import { PostFieldMap } from "@library/features/discussions/forms/PostSettings.types";
import { getFormattedValue, visibilityIcon } from "@library/features/discussions/forms/PostSettingsUtils";
import { TokenItem } from "@library/metas/TokenItem";
import { ToolTip } from "@library/toolTip/ToolTip";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { labelize } from "@vanilla/utils";

interface IPostSettingChangeSummaryProps {
    discussion: IDiscussion;
    targetCategory?: ICategory;
    redirect?: boolean;
    currentPostType?: PostType;
    targetPostType?: PostType;
    postFieldMap?: Record<PostFieldMap["currentField"], PostFieldMap>;
}
export function PostSettingChangeSummary(props: IPostSettingChangeSummaryProps) {
    const { discussion, targetCategory, redirect, currentPostType, targetPostType, postFieldMap } = props;

    const targetPostFieldsByPostFieldID = targetPostType?.postFields?.reduce((acc, field) => {
        return {
            ...acc,
            [field.postFieldID]: field,
        };
    }, {});

    const classes = postSettingsFormClasses();
    const summaryClasses = classes.summary;

    const isMove = props.discussion.categoryID !== targetCategory?.categoryID;
    const isChangeType = props.discussion.postTypeID !== targetPostType?.postTypeID;
    const onlyNewPostFields = !currentPostType?.postFields;

    // Get target fields that are being set (not mapped from any current field)
    const getSetFields = () => {
        if (!targetPostType?.postFields || !postFieldMap) {
            return [];
        }

        const mappedTargetFieldIds = new Set(
            Object.values(postFieldMap)
                .filter((mapping) => mapping.currentFieldValue === undefined)
                .map((mapping) => mapping.targetField),
        );

        return targetPostType.postFields.filter((field) => mappedTargetFieldIds.has(field.postFieldID));
    };

    const setFields = getSetFields();

    const renderMappedRow = (field: PostField) => {
        if (!field) {
            return null;
        }

        const currentField = field;
        const currentFieldID = field.postFieldID;
        const { currentFieldValue, targetFieldValue } = postFieldMap?.[currentFieldID] ?? {};
        const targetField = targetPostFieldsByPostFieldID?.[postFieldMap?.[currentFieldID]?.targetField ?? ""];

        const discardedField = !targetField;

        return (
            <div
                key={currentField.postFieldID}
                className={summaryClasses.mappingLayout}
                data-testid={`mapping-${currentField.postFieldID}`}
            >
                {discardedField ? (
                    <div className={summaryClasses.discardedField}>
                        <span>
                            <div className={summaryClasses.discardedFieldLabel}>
                                {currentField.label} <Icon icon={"status-alert"} />
                            </div>
                            <div className={summaryClasses.discardedFieldValue}>
                                {getFormattedValue(currentField, currentFieldValue)}
                            </div>
                            <div className={summaryClasses.discardedFieldWarning}>
                                {t("This field will be deleted from the post.")}
                            </div>
                        </span>
                    </div>
                ) : (
                    <>
                        <div className={summaryClasses.mappingCurrent}>
                            <div className={summaryClasses.mappingFieldName}>
                                {currentField.isRequired ? (
                                    <ToolTip label={t("Required field")}>
                                        <span aria-label={t("required")} className={cx(classes.labelRequired)}>
                                            *
                                        </span>
                                    </ToolTip>
                                ) : (
                                    ""
                                )}{" "}
                                {currentField.label}
                            </div>
                            <div className={summaryClasses.mappingFieldValue}>
                                {getFormattedValue(currentField, currentFieldValue)}
                            </div>
                            <div className={summaryClasses.mappingFieldMeta}>
                                <TokenItem>{labelize(currentField.formType)}</TokenItem>
                                <TokenItem>
                                    <span className={classes.iconToken}>
                                        {visibilityIcon(currentField.visibility)} {labelize(currentField.visibility)}
                                    </span>
                                </TokenItem>
                            </div>
                        </div>
                        <div>
                            <Icon icon={"move-right"} />
                        </div>
                        <div className={summaryClasses.mappingTarget}>
                            <div className={summaryClasses.mappingFieldName}>
                                {targetField.isRequired ? (
                                    <ToolTip label={t("Required field")}>
                                        <span aria-label={t("required")} className={cx(classes.labelRequired)}>
                                            *
                                        </span>
                                    </ToolTip>
                                ) : (
                                    ""
                                )}{" "}
                                {targetField.label}
                            </div>
                            <div className={summaryClasses.mappingFieldValue}>
                                {getFormattedValue(targetField, postFieldMap?.[currentFieldID]?.targetFieldValue)}
                            </div>
                            <div className={summaryClasses.mappingFieldMeta}>
                                <TokenItem>{labelize(targetField.formType)}</TokenItem>
                                <TokenItem>
                                    <span className={classes.iconToken}>
                                        {visibilityIcon(targetField.visibility)} {labelize(targetField.visibility)}
                                    </span>
                                </TokenItem>
                            </div>
                        </div>
                    </>
                )}
            </div>
        );
    };

    const renderSetField = (field: PostField) => {
        // Find the mapping for this set field (if any)
        const fieldMapping = Object.values(postFieldMap || {}).find(
            (mapping) => mapping.targetField === field.postFieldID,
        );

        return (
            <div
                key={field.postFieldID}
                className={summaryClasses.mappingLayout}
                data-testid={`set-field-${field.postFieldID}`}
            >
                <div className={summaryClasses.mappingCurrent}>
                    <div className={summaryClasses.newFieldIndicator}>
                        <Icon icon={"status-success"} />
                        {t("New Field")}
                    </div>
                </div>
                <div>
                    <Icon icon={"move-right"} />
                </div>
                <div className={summaryClasses.mappingTarget}>
                    <div className={summaryClasses.mappingFieldName}>
                        {field.isRequired ? (
                            <ToolTip label={t("Required field")}>
                                <span aria-label={t("required")} className={cx(classes.labelRequired)}>
                                    *
                                </span>
                            </ToolTip>
                        ) : (
                            ""
                        )}{" "}
                        {field.label}
                    </div>
                    <div className={summaryClasses.mappingFieldValue}>
                        {getFormattedValue(field, fieldMapping?.targetFieldValue)}
                    </div>
                    <div className={summaryClasses.mappingFieldMeta}>
                        <TokenItem>{labelize(field.formType)}</TokenItem>
                        <TokenItem>
                            <span className={classes.iconToken}>
                                {visibilityIcon(field.visibility)} {labelize(field.visibility)}
                            </span>
                        </TokenItem>
                    </div>
                </div>
            </div>
        );
    };

    const samePostType = currentPostType?.postTypeID === targetPostType?.postTypeID;
    const sameCategory = discussion.categoryID === targetCategory?.categoryID;

    return (
        <>
            {isMove && (
                <div className={summaryClasses.layout}>
                    <div className={summaryClasses.postName}>{discussion.name}</div>
                    <div className={summaryClasses.categories}>
                        {discussion.category?.name} <Icon icon={"move-right"} size={"compact"} /> {targetCategory?.name}
                    </div>
                    <div className={summaryClasses.redirect}>
                        {redirect && t("A redirect link will be left in the original category.")}
                    </div>
                </div>
            )}
            {isChangeType && (
                <>
                    <div className={cx(summaryClasses.mappingLayout, summaryClasses.mappingHeader)}>
                        <span>
                            {getIconForPostType(currentPostType?.parentPostTypeID ?? currentPostType?.postTypeID ?? "")}
                            {currentPostType?.name}
                        </span>
                        <span>
                            <Icon icon={"move-right"} />
                        </span>
                        <span>
                            {getIconForPostType(targetPostType?.parentPostTypeID ?? targetPostType?.postTypeID ?? "")}
                            {targetPostType?.name}
                        </span>
                    </div>

                    {currentPostType?.postFields?.map(renderMappedRow)}
                    {setFields.length > 0 && setFields.map(renderSetField)}
                </>
            )}

            {samePostType && sameCategory && (
                <div className={summaryClasses.mappingLayout}>
                    <div className={summaryClasses.discardedField}>
                        <span>
                            <div className={summaryClasses.discardedFieldWarning}>{t("No changes selected")}</div>
                        </span>
                    </div>
                </div>
            )}
        </>
    );
}
