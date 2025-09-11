/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { PostType } from "@dashboard/postTypes/postType.types";
import { getIconForPostType } from "@dashboard/postTypes/utils";
import Translate from "@library/content/Translate";
import { postSettingsFormClasses } from "@library/features/discussions/forms/PostSettings.classes";
import { PostFieldMap } from "@library/features/discussions/forms/PostSettings.types";
import { FormControl } from "@library/forms/FormControl";
import { type IControlProps, type IFieldError } from "@library/json-schema-forms";
import Heading from "@library/layout/Heading";
import { buildSchemaFromPostFields } from "@vanilla/addon-vanilla/createPost/utils";
import { t } from "@vanilla/i18n";
import { useMemo } from "react";

interface IPostSettingsFieldValidationProps {
    targetPostType: PostType;
    postFieldMap: Record<PostFieldMap["currentField"], PostFieldMap>;
    setPostFieldMap: (postFieldMap: PostFieldMap) => void;
    fieldErrors?: Record<string, IFieldError[]>;
}

export function PostSettingsFieldValidation(props: IPostSettingsFieldValidationProps) {
    const { fieldErrors, targetPostType, postFieldMap, setPostFieldMap } = props;

    const classes = postSettingsFormClasses();
    const targetPostFields = targetPostType?.postFields ?? [];

    // Generate schema and instance for the form
    const targetPostFieldSchema = useMemo(() => {
        return buildSchemaFromPostFields(targetPostFields);
    }, [targetPostFields]);

    const targetPostFieldInstance = useMemo(() => {
        return Object.entries(postFieldMap).reduce((acc, [key, value]) => {
            const targetField = targetPostFields.find((field) => field.postFieldID === value.targetField);

            if (value.targetField && value.targetField !== "unmapped") {
                let finalValue = value.targetFieldValue;

                if (targetField?.dataType === "boolean" && typeof value.targetFieldValue === "string") {
                    finalValue = value.targetFieldValue === "true" ? true : false;
                }

                return {
                    ...acc,
                    [value.targetField]: finalValue,
                };
            }
            return acc;
        }, {});
    }, [postFieldMap]);

    const handleFormChange = (event: () => Record<string, any>) => {
        const delta = event();
        const changedKey = Object.keys(delta)[0];

        // Find the existing mapping for this target field or create a new one
        const existingMapping = Object.values(postFieldMap).find((mapping) => mapping.targetField === changedKey);

        const newPostFieldMap: PostFieldMap = {
            currentField: existingMapping?.currentField ?? `unmapped-${changedKey}`,
            targetField: changedKey,
            currentFieldValue: existingMapping?.currentFieldValue,
            targetFieldValue: delta[changedKey],
        };

        setPostFieldMap(newPostFieldMap);
    };

    return (
        <>
            <div style={{ paddingBlock: 16 }}>
                <div className={classes.fieldValidationHeader}>
                    <Heading depth={3}>
                        {getIconForPostType(targetPostType?.parentPostTypeID ?? targetPostType.postTypeID)}
                        <Translate source={"Validate <0/> Fields"} c0={targetPostType.name} />
                    </Heading>
                    <p>{t("Review and update the field values for the new post type before proceeding.")}</p>
                </div>

                <DashboardSchemaForm
                    fieldErrors={
                        fieldErrors
                            ? Object.fromEntries(
                                  Object.entries(fieldErrors).map(([key, errors]) => {
                                      return [
                                          key,
                                          errors.map((err) => {
                                              // Strip the nested path from the error path.
                                              let newPath = err.path?.replace("postMeta", "");
                                              if (!newPath) {
                                                  newPath = undefined;
                                              }
                                              return {
                                                  ...err,
                                                  path: newPath,
                                              };
                                          }),
                                      ];
                                  }),
                              )
                            : undefined
                    }
                    schema={targetPostFieldSchema}
                    instance={targetPostFieldInstance}
                    onChange={handleFormChange}
                />
            </div>
        </>
    );
}

function FormControlWithFixedDateRange(props: IControlProps) {
    return <FormControl {...props} dateRangeDirection={"below"} />;
}
