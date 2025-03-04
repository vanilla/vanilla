/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useState, useEffect, useRef } from "react";
import { t } from "@vanilla/i18n";
import { useFormik } from "formik";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { usePostTypeQuery } from "@dashboard/postTypes/postType.hooks";
import { NestedSelect } from "@library/forms/nestedSelect";
import { SchemaFormBuilder } from "@library/json-schema-forms";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { PostField } from "@dashboard/postTypes/postType.types";
import { useMutation } from "@tanstack/react-query";
import { DiscussionsApi } from "@vanilla/addon-vanilla/posts/DiscussionsApi";
import { useToast } from "@library/features/toaster/ToastContext";
import Translate from "@library/content/Translate";
import { IFieldError, IJsonSchemaFormHandle } from "@vanilla/json-schema-forms";
import { mapValidationErrorsToFormikErrors } from "@vanilla/json-schema-forms/src/utils";
import ModalConfirm from "@library/modal/ModalConfirm";
import { CreatableFieldDataType } from "@dashboard/userProfiles/types/UserProfiles.types";
import { RecordID } from "@vanilla/utils";
import { convertPostFormClasses } from "@library/features/discussions/forms/ConvertPostForm.classes";

const DISCARD_INFO_FIELD = {
    value: "discard-this-info",
    label: t("Discard this information"),
};
interface IGetPostFieldOptions {
    postFields: PostField[] | undefined;
    omitPublic?: boolean;
    type: CreatableFieldDataType;
}

function getPostFieldOptions(params: IGetPostFieldOptions) {
    const { postFields, omitPublic, type } = params;

    if (!postFields) {
        return [];
    }

    const returnValue = omitPublic
        ? postFields
              .filter((field) => field.visibility !== "public")
              .filter((field) => field.dataType === type)
              .map((field) => ({
                  value: field.postFieldID,
                  label: field.label,
              }))
        : postFields
              .filter((field) => field.dataType === type)
              .map((field) => ({
                  value: field.postFieldID,
                  label: field.label,
              }));

    return returnValue.concat([DISCARD_INFO_FIELD]);
}

interface IProps {
    onClose: () => void;
    onSuccess?: () => Promise<void>;
    discussionID: RecordID;
    allPostTypes?: any;
    sourcePostTypeID?: string;
    sourcePostMeta?: Record<PostField["postFieldID"], any>;
}

export function ConvertPostFormImpl(props: IProps) {
    const { onClose, onSuccess, discussionID, allPostTypes, sourcePostTypeID, sourcePostMeta } = props;
    const toast = useToast();

    const schemaFormRef = useRef<IJsonSchemaFormHandle | null>(null);

    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]>>({});

    const currentPostTypeDefinition = allPostTypes?.find((postType) => postType.postTypeID === sourcePostTypeID);

    const postTypeOptions = allPostTypes?.map((postType) => ({ label: postType.name, value: postType.postTypeID }));

    const postFieldsSource = currentPostTypeDefinition?.postFields;

    const [postFieldsDestination, setPostFieldsDestination] = useState<PostField[] | undefined>(undefined);

    const [destinationPostType, setDestinationPostType] = useState<string | undefined>(sourcePostTypeID);

    useEffect(() => {
        if (!destinationPostType) {
            return;
        }
        const destinationPostTypeDefinition = allPostTypes?.find(
            (postType) => postType.postTypeID === destinationPostType,
        );
        const postFieldsDestination = destinationPostTypeDefinition?.postFields;
        setPostFieldsDestination(postFieldsDestination);
    }, [destinationPostType]);

    const [mapSchema, setMapSchema] = useState(SchemaFormBuilder.create());

    postFieldsSource?.forEach((postField) => {
        const sourceFieldValue = sourcePostMeta && sourcePostMeta[postField?.postFieldID];

        const currentDestinationOptions =
            postField.visibility === "public"
                ? getPostFieldOptions({
                      postFields: postFieldsDestination,
                      type: postField.dataType as CreatableFieldDataType,
                  })
                : getPostFieldOptions({
                      postFields: postFieldsDestination,
                      type: postField.dataType as CreatableFieldDataType,
                      omitPublic: true,
                  });

        // If the source is public, it can be mapped to any field (public -> private or public -> internal is okay)
        // If it's private or internal, public fields should be omitted from the mapping options
        // const options = postField.visibility === "public" ? destinationOptions : destinationOptionsWithoutPublic;
        const options = currentDestinationOptions;

        mapSchema
            .selectStatic(postField.postFieldID, postField.label, postField.description, options)
            .withTooltip(`User submitted value: ${sourceFieldValue}`);
    });

    const sourceIDs = postFieldsSource?.map((field) => field.postFieldID);
    const [unmappedSourceIDs, setUnmappedSourceIDs] = useState<string[]>([]);
    const [unmappedRequiredDestinationIDs, setUnmappedRequiredDestinationIDs] = useState<string[]>([]);

    function getInitialValues() {
        const initialValues: any = {};

        postFieldsDestination?.forEach((postField) => {
            const matchingField = postFieldsSource?.find((field) => field.postFieldID === postField.postFieldID);
            initialValues[postField.postFieldID] = matchingField?.postFieldID || "";
        });
        return initialValues;
    }

    const mutation = useMutation({
        mutationFn: async ({ postTypeID, postMeta }: { postTypeID: string; postMeta: Record<string, any> }) => {
            return await DiscussionsApi.convert(discussionID, {
                postTypeID: postTypeID,
                postMeta,
            });
        },
    });

    function mapPostFields(values) {
        const destinationPostFields = allPostTypes?.find(
            (postType) => postType.postTypeID === destinationPostType,
        )?.postFields;

        const destinationPostMeta = {};

        destinationPostFields?.forEach((fieldDef) => {
            const postFieldID = fieldDef.postFieldID;
            const mappedField = values[postFieldID];
            const valueToMap = sourcePostMeta ? sourcePostMeta[mappedField] : undefined;
            destinationPostMeta[postFieldID] = valueToMap;
        });
        return destinationPostMeta;
    }

    const { values, setValues, submitForm, isSubmitting, dirty } = useFormik<any>({
        initialValues: {
            destinationPostType: destinationPostType,
            ...getInitialValues(),
        },
        validate: () => {
            const result = schemaFormRef?.current?.validate();
            const mappedErrors = mapValidationErrorsToFormikErrors(result?.errors ?? []);
            return mappedErrors ?? {};
        },

        onSubmit: async () => {
            try {
                if (!destinationPostType) {
                    console.error("You need to choose a destination post type");
                }
                const constructedPostMeta = mapPostFields(values);

                await mutation.mutateAsync({ postTypeID: destinationPostType ?? "", postMeta: constructedPostMeta });

                toast.addToast({
                    autoDismiss: false,
                    body: <Translate source="Success! Post converted." />,
                });

                void onSuccess?.();
            } catch (e) {
                console.error("Error converting post type", e);
                if (e.errors) {
                    const formattedErrors = Object.keys(e.errors).reduce((acc, key) => {
                        const errorArray = e.errors[key];
                        return {
                            ...acc,
                            [key]: errorArray.map((err) => ({
                                // Don't include path, it will be set to postMeta but our form values aren't nested like that
                                field: err.field,
                                message: err.message?.replace("postMeta.", ""),
                                code: err.code,
                                status: err.status,
                            })),
                        };
                    }, {});

                    setFieldErrors(formattedErrors);
                }
                toast.addToast({
                    autoDismiss: false,
                    dismissible: true,
                    body: <Translate source="There was an error changing the type of this post." />,
                });
            }
        },
        enableReinitialize: true,
    });

    //
    useEffect(() => {
        const usedSourceFields = Object.keys(values)
            .filter((key) => key !== "destinationPostType")
            .filter((key) => values[key] && values[key] !== "");

        const unusedSourceFields = sourceIDs?.filter((sourceID) => !usedSourceFields.includes(sourceID));

        setUnmappedSourceIDs(unusedSourceFields ?? []);

        const requiredDestinationIDs = postFieldsDestination
            ? postFieldsDestination.filter((field) => field.isRequired).map((field) => field.postFieldID)
            : [];

        const currentlyUnmappedDestinationIDs = requiredDestinationIDs.filter(
            (id) => !Object.values(values).includes(id),
        );

        setUnmappedRequiredDestinationIDs(currentlyUnmappedDestinationIDs);
    }, [values]);

    const [showConfirmCancel, setShowConfirmCancel] = useState(false);

    function handleCancel() {
        if (dirty) {
            setShowConfirmCancel(true);
        } else {
            onClose();
        }
    }
    return (
        <form
            role="form"
            onSubmit={async (e) => {
                e.preventDefault();
                await submitForm();
            }}
        >
            <ModalConfirm
                isVisible={showConfirmCancel}
                title={t("Cancel Post Conversion")}
                onCancel={() => setShowConfirmCancel(false)}
                onConfirm={onClose}
            >
                {t("Are you sure you want to cancel this post conversion?")}
            </ModalConfirm>

            <Frame
                header={<FrameHeader closeFrame={onClose} title={t("Change Post Type")} />}
                body={
                    <FrameBody hasVerticalPadding>
                        <NestedSelect
                            label="Current post type"
                            value={currentPostTypeDefinition?.postTypeID}
                            options={[
                                {
                                    label: currentPostTypeDefinition?.name,
                                    value: currentPostTypeDefinition?.postTypeID,
                                },
                            ]}
                            onChange={() => {}}
                            required
                            disabled
                        />

                        <NestedSelect
                            label="Select post type to change to"
                            options={postTypeOptions}
                            onChange={(selectedPostType: string) => {
                                setDestinationPostType(selectedPostType);
                                void setValues({ ...values, destinationPostType: selectedPostType });
                            }}
                            value={values.destinationPostType}
                        />

                        {!postFieldsSource && !postFieldsDestination ? (
                            <p className={convertPostFormClasses().warningContainer}>
                                {t("No custom fields used in either post type")}
                            </p>
                        ) : (
                            <p className={convertPostFormClasses().warningContainer}>
                                {t(
                                    "Field Mapping: Map the fields from the current post type to the corresponding fields in the new post type",
                                )}
                            </p>
                        )}

                        <DashboardSchemaForm
                            instance={values}
                            schema={mapSchema.getSchema()}
                            onChange={setValues}
                            fieldErrors={fieldErrors}
                            ref={schemaFormRef}
                        />

                        {unmappedRequiredDestinationIDs.length > 0 && (
                            <p className={convertPostFormClasses().errorContainer}>
                                <b>
                                    {unmappedRequiredDestinationIDs.map((id) => {
                                        const field = postFieldsDestination?.find((field) => field.postFieldID === id);
                                        return `${field?.label} `;
                                    })}
                                </b>
                                {t(
                                    " field(s) are required for this post type. You must map data to these required fields to continue.",
                                )}
                            </p>
                        )}

                        {values.destinationPostType && unmappedSourceIDs.length > 0 && (
                            <p className={convertPostFormClasses().warningContainer}>
                                {t("You have not mapped field(s):")}{" "}
                                <b>
                                    {unmappedSourceIDs.map((fieldID) => {
                                        const field = postFieldsSource?.find((field) => field.postFieldID === fieldID);
                                        return `${field?.label}, `;
                                    })}
                                </b>
                                {
                                    " to another field in the new post type. If you proceed with the conversion, the information from those fields will be lost."
                                }
                            </p>
                        )}
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button buttonType={ButtonTypes.TEXT} onClick={handleCancel}>
                            {t("Cancel")}
                        </Button>

                        <Button
                            disabled={isSubmitting || unmappedRequiredDestinationIDs.length > 0}
                            type="submit"
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                        >
                            {t("Save")}
                        </Button>
                    </FrameFooter>
                }
            />
        </form>
    );
}

interface ILoadableProps {
    onClose: () => void;
    onSuccess?: () => Promise<void>;
    discussion: IDiscussion;
}
export default function ConvertPostFormLoadable(props: ILoadableProps) {
    const { onClose, discussion, onSuccess } = props;

    const postTypesQuery = usePostTypeQuery({ expand: ["all"] });

    const { data: allPostTypes } = postTypesQuery;

    const postTypeID = discussion.postTypeID;

    return (
        <ConvertPostFormImpl
            onClose={onClose}
            allPostTypes={allPostTypes}
            sourcePostTypeID={postTypeID}
            discussionID={discussion.discussionID}
            sourcePostMeta={discussion.postMeta}
            onSuccess={onSuccess}
        />
    );
}
