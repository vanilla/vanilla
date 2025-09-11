/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { dashboardFormGroupClasses } from "@dashboard/forms/DashboardFormGroup.classes";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { PostType, PostField } from "@dashboard/postTypes/postType.types";
import { getIconForPostType } from "@dashboard/postTypes/utils";
import { css, cx } from "@emotion/css";
import Translate from "@library/content/Translate";
import { postSettingsFormClasses } from "@library/features/discussions/forms/PostSettings.classes";
import { PostFieldMap } from "@library/features/discussions/forms/PostSettings.types";
import { getFormattedValue, visibilityIcon } from "@library/features/discussions/forms/PostSettingsUtils";
import DatePicker from "@library/forms/DatePicker";
import InputTextBlock from "@library/forms/InputTextBlock";
import { NestedSelect } from "@library/forms/nestedSelect";
import { Select } from "@library/json-schema-forms";
import Heading from "@library/layout/Heading";
import Message from "@library/messages/Message";
import { TokenItem } from "@library/metas/TokenItem";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { ColorVar } from "@library/styles/CssVar";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { useStackingContext } from "@vanilla/react-utils";
import { FormGroup, FormGroupInput, FormGroupLabel } from "@vanilla/ui";
import { labelize, notEmpty } from "@vanilla/utils";
import React, { useMemo } from "react";

interface IPostFieldMappingUIProps {
    discussion: IDiscussion;
    currentPostType: PostType;
    targetPostType: PostType;
    postFieldMap?: Record<PostFieldMap["currentField"], PostFieldMap>;
    setPostFieldMap?: (postFieldMap: PostFieldMap) => void;
}

export function PostSettingsFieldMapper(props: IPostFieldMappingUIProps) {
    const { discussion, currentPostType, targetPostType, postFieldMap, setPostFieldMap } = props;

    const currentPostFields = currentPostType?.postFields ?? [];
    const targetPostFields = targetPostType?.postFields ?? [];

    let allPostFields: PostField[] = [...currentPostFields, ...targetPostFields];
    // Make sure we don't have any duplicate fields
    allPostFields = allPostFields.filter(
        (field, index, self) => self.findIndex((t) => t.postFieldID === field.postFieldID) === index,
    );

    const classes = postSettingsFormClasses();

    const availablePostFields = useMemo<Select.Option[]>(() => {
        const assignedFields = Object.values(postFieldMap ?? {}).map((field) => field.targetField);
        const allFieldsAsOptions = targetPostFields.map((field) => {
            return {
                label: field.label,
                value: field.postFieldID,
                data: field,
            };
        });
        return [
            { label: "No mapping", value: "unmapped" },
            ...allFieldsAsOptions.filter((field) => !assignedFields.includes(field.value)),
        ];
    }, [postFieldMap]);

    return (
        <>
            <div className={cx(classes.layout, classes.header)}>
                <span>
                    {getIconForPostType(currentPostType?.parentPostTypeID ?? currentPostType.postTypeID)}
                    <Translate source={"<0/> Field"} c0={currentPostType.name} />
                </span>
                <span>
                    <Icon icon={"move-right"} />
                </span>
                <span>
                    {getIconForPostType(targetPostType?.parentPostTypeID ?? targetPostType.postTypeID)}
                    <Translate source={"<0/> Field"} c0={targetPostType.name} />
                </span>
            </div>
            {currentPostFields.map((field) => (
                <PostSettingMappingRow
                    key={field.postFieldID}
                    field={field}
                    currentValue={discussion?.postMeta?.[field.postFieldID]}
                    targetPostFields={targetPostFields}
                    availablePostFields={availablePostFields}
                    postFieldMap={postFieldMap?.[field.postFieldID]}
                    updatePostFieldMap={(updatedFieldMap: PostFieldMap) => {
                        setPostFieldMap?.(updatedFieldMap);
                    }}
                />
            ))}
        </>
    );
}

interface IPostSettingMappingRowProps {
    field: PostField;
    currentValue?: string;
    targetPostFields: PostField[];
    postFieldMap?: PostFieldMap;
    updatePostFieldMap?: (postFieldMap: PostFieldMap) => void;
    availablePostFields: Select.Option[];
}

function PostSettingMappingRow(props: IPostSettingMappingRowProps) {
    const { field, currentValue, targetPostFields, postFieldMap, updatePostFieldMap, availablePostFields } = props;
    const formGroupClass = dashboardFormGroupClasses();
    const dashboardClass = dashboardClasses();
    const classes = postSettingsFormClasses();

    const { zIndex } = useStackingContext();

    const targetFieldsByPostFieldID: Record<PostField["postFieldID"], PostField> = targetPostFields.reduce(
        (acc, field) => {
            return {
                ...acc,
                [field.postFieldID]: field,
            };
        },
        {},
    );

    const thisPostFieldOption: Select.Option[] = targetPostFields
        .map((field) => {
            if (field.postFieldID === postFieldMap?.targetField) {
                return {
                    label: field.label,
                    value: field.postFieldID,
                    extraLabel: field.dataType,
                    data: field,
                };
            }
        })
        .filter(notEmpty);

    const availablePostFieldsWithSelected = [...availablePostFields, ...thisPostFieldOption];

    const { targetField } = postFieldMap ?? {};
    const targetPostField = targetField ? targetFieldsByPostFieldID[targetField] : undefined;
    const isConvertingValueType = targetPostField?.dataType !== field.dataType;
    const isConvertingValueVisibility = targetPostField?.visibility !== field.visibility;

    const newValueDropDownOptions = () => {
        if (["tokens", "dropdown"].includes(targetPostField?.formType ?? "")) {
            return targetPostField?.dropdownOptions?.map((option) => ({ label: option, value: option })) ?? [];
        }
        if (targetPostField?.formType === "checkbox") {
            return [
                { label: "Yes", value: "true" },
                { label: "No", value: "false" },
            ];
        }
        return [];
    };

    const validationMessages = () => {
        let messages: string[] = [];

        if (!targetField || targetField === "unmapped") {
            return (
                <div className={classes.validationMessage} style={{ color: ColorsUtils.var(ColorVar.Red) }}>
                    <Icon icon={"delete"} size={"compact"} /> {t("This field will be deleted from the post.")}
                </div>
            );
        }

        if (isConvertingValueType) {
            messages.push(`This fields type will be changed.`);
        }
        if (isConvertingValueVisibility) {
            messages.push(`This fields visibility will be changed.`);
        }
        return messages.map((message) => (
            <div className={classes.validationMessage} key={message}>
                <Icon icon={"status-warning"} size={"compact"} /> {message}
            </div>
        ));
    };

    const formatValueForMap = (value: any, postField?: PostField) => {
        if (!postField) {
            return value;
        }
        if (postField.dataType === "boolean") {
            const res = value === true || value === "true" ? "true" : "false";
            return res;
        }
        if (postField.dataType === "date") {
            const date = new Date(value);
            if (isNaN(date.getTime())) {
                return new Date().toISOString().split("T")[0];
            }
            return new Date(value).toISOString().split("T")[0];
        }
        if (postField.dataType === "string[]") {
            return value?.join(", ");
        }
    };

    const isTargetFieldRequired = targetFieldsByPostFieldID[postFieldMap?.targetField ?? ""]?.isRequired;
    const newRemappedValue = () => {
        if (targetPostField?.formType === "tokens") {
            return Array.isArray(postFieldMap?.targetFieldValue ?? currentValue)
                ? postFieldMap?.targetFieldValue ?? currentValue
                : [];
        }

        const res = postFieldMap?.targetFieldValue ?? currentValue;

        if (targetPostField?.formType === "checkbox" && typeof res === "boolean") {
            return res === true ? "true" : "false";
        }

        return res;
    };
    const newRemappedDateValue = () => {
        const date = new Date(postFieldMap?.targetFieldValue ?? currentValue ?? "");
        if (isNaN(date.getTime())) {
            return new Date().toISOString().split("T")[0];
        }
        return date.toISOString().split("T")[0];
    };

    let descriptionTooltip: React.ReactNode = null;
    if (field.description) {
        descriptionTooltip = (
            <ToolTip label={field.description}>
                <ToolTipIcon>
                    <Icon icon={"info"} />
                </ToolTipIcon>
            </ToolTip>
        );
    }

    const isUnmapped = !targetField || targetField === "unmapped";

    return (
        <div className={cx(classes.layout)}>
            <div className={cx(classes.current)}>
                <span className={cx(dashboardClass.label, classes.label)}>
                    {field.isRequired ? (
                        <ToolTip label={t("Required field")}>
                            <span aria-label={t("required")} className={cx(dashboardClasses().labelRequired)}>
                                *
                            </span>
                        </ToolTip>
                    ) : (
                        ""
                    )}{" "}
                    {field.label} {descriptionTooltip}
                </span>
                <span className={cx(formGroupClass.labelInfo, !currentValue && classes.emptyValue)}>
                    {getFormattedValue(field, currentValue)}
                </span>
                <div className={classes.meta}>
                    <TokenItem>{labelize(field.formType)}</TokenItem>
                    <TokenItem>
                        <span className={classes.iconToken}>
                            {visibilityIcon(field.visibility)} {labelize(field.visibility)}
                        </span>
                    </TokenItem>
                </div>
            </div>
            <div>{targetField === "unmapped" ? <></> : <Icon icon={"move-right"} />}</div>
            <div className={classes.target}>
                <div>
                    <FormGroup>
                        <FormGroupLabel>{t("New Field")}</FormGroupLabel>
                        <FormGroupInput className={classes.newFieldInput}>
                            <NestedSelect
                                onChange={(value: string) => {
                                    updatePostFieldMap?.({
                                        currentField: field.postFieldID,
                                        targetField: value ?? "unmapped",
                                        currentFieldValue: currentValue,
                                        targetFieldValue:
                                            formatValueForMap(postFieldMap?.targetFieldValue, targetPostField) ??
                                            formatValueForMap(currentValue, field) ??
                                            currentValue,
                                    });
                                }}
                                value={postFieldMap?.targetField ?? "unmapped"}
                                options={availablePostFieldsWithSelected}
                            />
                            <ToolTip
                                label={
                                    isUnmapped
                                        ? t("Without a mapping, this value will be lost in the conversion")
                                        : targetPostField?.description
                                }
                            >
                                <span>
                                    {
                                        <Icon
                                            style={{ color: isUnmapped ? ColorsUtils.var(ColorVar.Red) : undefined }}
                                            icon={isUnmapped ? "status-alert" : "info"}
                                        />
                                    }
                                </span>
                            </ToolTip>
                        </FormGroupInput>
                    </FormGroup>
                    {!postFieldMap?.targetField ||
                        (postFieldMap?.targetField !== "unmapped" && (
                            <FormGroup>
                                <FormGroupLabel className={classes.additionalOptionsLabel}>
                                    {isTargetFieldRequired ? (
                                        <ToolTip label={t("Required field")}>
                                            <span aria-label={t("required")} className={cx(classes.labelRequired)}>
                                                *
                                            </span>
                                        </ToolTip>
                                    ) : (
                                        ""
                                    )}{" "}
                                    {t("New Value")}
                                </FormGroupLabel>
                                <FormGroupInput className={classes.newFieldInput}>
                                    {targetPostField?.formType === "date" ? (
                                        <DatePicker
                                            classNames={classes.dayPicker}
                                            onChange={(value) => {
                                                updatePostFieldMap?.({
                                                    currentField: field.postFieldID,
                                                    targetField: postFieldMap?.targetField ?? "unmapped",
                                                    currentFieldValue: formatValueForMap(currentValue, field),
                                                    targetFieldValue: formatValueForMap(value, targetPostField),
                                                });
                                            }}
                                            value={newRemappedDateValue()}
                                            datePickerDropdownClassName={css({
                                                zIndex: zIndex,
                                                top: -350,
                                            })}
                                            required={isTargetFieldRequired}
                                        />
                                    ) : (
                                        <>
                                            {["text", "text-multiline", "number"].includes(
                                                targetPostField?.formType ?? "",
                                            ) ? (
                                                <InputTextBlock
                                                    inputProps={{
                                                        value: newRemappedValue(),
                                                        required: isTargetFieldRequired,
                                                        multiline: targetPostField?.formType === "text-multiline",
                                                        className:
                                                            targetPostField?.formType === "text-multiline"
                                                                ? dashboardClasses().multiLineInput
                                                                : undefined,
                                                        onChange: (changeEvent) => {
                                                            updatePostFieldMap?.({
                                                                currentField: field.postFieldID,
                                                                targetField: postFieldMap?.targetField ?? "unmapped",
                                                                currentFieldValue: currentValue,
                                                                targetFieldValue:
                                                                    changeEvent.target.value ?? currentValue,
                                                            });
                                                        },
                                                    }}
                                                    multiLineProps={{
                                                        rows: 4,
                                                        maxRows: 6,
                                                    }}
                                                    noMargin={true}
                                                    required={isTargetFieldRequired}
                                                />
                                            ) : (
                                                <NestedSelect
                                                    onChange={(value) => {
                                                        updatePostFieldMap?.({
                                                            currentField: field.postFieldID,
                                                            targetField: postFieldMap?.targetField ?? "unmapped",
                                                            currentFieldValue: currentValue,
                                                            targetFieldValue: value ?? currentValue,
                                                        });
                                                    }}
                                                    value={newRemappedValue()}
                                                    options={newValueDropDownOptions()}
                                                    required={isTargetFieldRequired}
                                                    multiple={targetPostField?.formType === "tokens"}
                                                />
                                            )}
                                        </>
                                    )}

                                    <ToolTip label={t("You may update the value of this field here.")}>
                                        <span>{<Icon icon={"info"} />}</span>
                                    </ToolTip>
                                </FormGroupInput>
                            </FormGroup>
                        ))}
                </div>
                {validationMessages()}
                {targetPostField && (
                    <div className={classes.meta}>
                        <>
                            <TokenItem>{labelize(targetPostField.formType)}</TokenItem>
                            <TokenItem>
                                <span className={classes.iconToken}>
                                    {visibilityIcon(targetPostField.visibility)} {labelize(targetPostField.visibility)}
                                </span>
                            </TokenItem>
                        </>
                    </div>
                )}
            </div>
        </div>
    );
}
