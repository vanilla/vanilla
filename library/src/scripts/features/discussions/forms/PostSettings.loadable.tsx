/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { IDiscussion } from "@dashboard/@types/api/discussion";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { usePostTypeQuery } from "@dashboard/postTypes/postType.hooks";
import { useCategory } from "@library/categoriesWidget/CategoryList.hooks";
import Translate from "@library/content/Translate";
import type { IError } from "@library/errorPages/CoreErrorMessages";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import { postSettingsFormClasses } from "@library/features/discussions/forms/PostSettings.classes";
import { IPostSettingsProps, PostFieldMap } from "@library/features/discussions/forms/PostSettings.types";
import { PostSettingChangeSummary } from "@library/features/discussions/forms/PostSettingsChangeSummary";
import { PostSettingsFieldMapper } from "@library/features/discussions/forms/PostSettingsFieldMapper";
import { PostSettingsFieldValidation } from "@library/features/discussions/forms/PostSettingsFieldValidation";
import { FormSkeleton } from "@library/features/discussions/forms/PostSettingsFormSkeleton";
import { SteppedModalFooter } from "@library/features/discussions/forms/PostSettingsSteppedModalFooter";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import ErrorMessages from "@library/forms/ErrorMessages";
import type { NestedSelect } from "@library/forms/nestedSelect";
import { CategoryDropdown } from "@library/forms/nestedSelect/presets/CategoryDropdown";
import { SchemaFormBuilder, type IFieldError } from "@library/json-schema-forms";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Message from "@library/messages/Message";
import SmartLink from "@library/routing/links/SmartLink";
import { ToolTip } from "@library/toolTip/ToolTip";
import { getMeta } from "@library/utility/appUtils";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { DiscussionsApi } from "@vanilla/addon-vanilla/posts/DiscussionsApi";
import { t } from "@vanilla/i18n";
import { labelize, notEmpty, stableObjectHash } from "@vanilla/utils";
import { useEffect, useRef, useState } from "react";
import { sprintf } from "sprintf-js";

interface IPostSettingsImplProps extends IPostSettingsProps {
    initialAction: "move" | "change";
}

interface IPostSettingsFormValues {
    action: "move" | "change";
    categoryID: number;
    postTypeID: string;
    redirect: boolean;
}

interface IPostSettingsMutationParams {
    action: "move" | "change";
    categoryID?: ICategory["categoryID"];
    postTypeID?: string;
    postMeta?: Record<string, string | string[]>;
    addRedirects?: boolean;
}

interface IPostTypeFieldValidationResult {
    fieldErrors: Record<string, IFieldError[]>;
    disableNextReason: string | undefined;
    isSelectedPostTypeAllowed: boolean;
    categoryConfigError: boolean;
}

interface IFrameTitle {
    title: JSX.Element;
    finalizeLabel: string;
    isMove: boolean;
    actionWillMovePost: boolean;
    isChangeType: boolean;
}

interface IPostSettingsFormState {
    formValues: IPostSettingsFormValues;
    setFormValues: (values: IPostSettingsFormValues) => void;
}

type PostSettingsSteps = "action" | "remap" | "fieldValidation" | "summary";
const POST_TYPES_ENABLED = getMeta("featureFlags.customLayout.createPost.Enabled", false);

// Combined mutation hook for move and change type operations
function usePostSettingsMutation(discussion: IDiscussion, onClose: () => void, handleSuccess?: () => void) {
    const toast = useToast();
    const queryClient = useQueryClient();
    const onDiscussionPage = window.location.href === discussion.url;

    return useMutation({
        mutationFn: async ({ action, categoryID, postTypeID, postMeta, addRedirects }: IPostSettingsMutationParams) => {
            const actionWillMovePost = categoryID && categoryID !== discussion.categoryID;
            const isChangeType = postTypeID && postTypeID !== (discussion?.postTypeID ?? discussion?.type);

            if (isChangeType && !actionWillMovePost) {
                // Change type only
                return await DiscussionsApi.convert(discussion.discussionID, {
                    postTypeID: postTypeID!,
                    ...(postMeta && { postMeta }),
                });
            } else if (actionWillMovePost) {
                // Move (with optional type change)
                return await DiscussionsApi.move({
                    discussionIDs: [discussion.discussionID],
                    categoryID: categoryID!,
                    ...(isChangeType && { postTypeID }),
                    ...(postMeta && isChangeType && { postMeta }),
                    addRedirects: addRedirects ?? false,
                });
            }
        },
        onSuccess: async (_, variables) => {
            const actionWillMovePost = variables.categoryID && variables.categoryID !== discussion.categoryID;
            const isChangeType =
                variables.postTypeID && variables.postTypeID !== (discussion?.postTypeID ?? discussion?.type);

            void queryClient.invalidateQueries(["discussionList"]);
            void queryClient.invalidateQueries(["discussion"]);

            const successMessage =
                actionWillMovePost && isChangeType
                    ? "Success! Post moved and type changed."
                    : actionWillMovePost
                    ? "Success! Post moved."
                    : "Success! Post type changed.";

            toast.addToast({
                dismissible: true,
                autoDismiss: onDiscussionPage,
                body: (
                    <>
                        <Translate source={successMessage} />
                        {!onDiscussionPage && (
                            <>
                                <br />
                                <SmartLink to={discussion.url}>Go to post</SmartLink>
                            </>
                        )}
                    </>
                ),
            });
            void handleSuccess?.();
            onClose();
        },
    });
}

// Common form state hook
function usePostSettingsFormState(discussion: IDiscussion, initialAction: "move" | "change"): IPostSettingsFormState {
    const [formValues, setFormValues] = useState<IPostSettingsFormValues>({
        action: initialAction,
        categoryID: discussion.categoryID,
        postTypeID: discussion?.postTypeID ?? discussion?.type ?? "discussion",
        redirect: false,
    });

    return {
        formValues,
        setFormValues,
    };
}

// Common post type field validation hook
function usePostTypeFieldValidation(
    selectedCategoryData: ICategory | undefined,
    allowedPostTypeIds: string[],
    formValues: IPostSettingsFormValues,
): IPostTypeFieldValidationResult {
    if (!selectedCategoryData) {
        // Data is loading, so we can't validate anything
        return {
            fieldErrors: {},
            disableNextReason: undefined,
            isSelectedPostTypeAllowed: false,
            categoryConfigError: false,
        };
    }
    const isMove = formValues.action === "move";

    const categoryConfigError =
        (selectedCategoryData?.hasRestrictedPostTypes && allowedPostTypeIds.length === 0) ?? false;

    const isSelectedPostTypeAllowed =
        selectedCategoryData?.hasRestrictedPostTypes || !POST_TYPES_ENABLED
            ? allowedPostTypeIds.includes(String(formValues?.postTypeID ?? ""))
            : true;

    const categoryFieldError = categoryConfigError
        ? {
              message: `This category's settings does not allow any post types. Please select another category.`,
              field: "categoryID",
          }
        : null;

    const postTypeFieldError =
        categoryFieldError == null && formValues?.postTypeID && !isSelectedPostTypeAllowed
            ? {
                  message: sprintf(t("This post type is not allowed in the selected category.")),
                  field: formValues.action === "move" ? "postTypeID" : "categoryID",
              }
            : null;

    const fieldErrors: Record<string, IFieldError[]> = {
        categoryID: [categoryFieldError, isMove ? postTypeFieldError : null].filter(notEmpty),
        postTypeID: [!isMove ? postTypeFieldError : null].filter(notEmpty),
    };

    const disableNextReason = Object.values(fieldErrors).some((errors) => errors.length > 0)
        ? Object.values(fieldErrors)
              .flat()
              .map((e) => e.message)
              .join(" ")
        : undefined;

    return {
        fieldErrors,
        disableNextReason,
        isSelectedPostTypeAllowed,
        categoryConfigError,
    };
}

// Common form schema builder hook
function usePostSettingsSchema(
    discussion: IDiscussion,
    formValues: IPostSettingsFormValues,
    postTypeOptions: NestedSelect.Option[],
) {
    const isMove = formValues.action === "move";

    let actionSchemaBase = new SchemaFormBuilder()
        .selectStatic(
            "action",
            "Action",
            "What would you like to do with this post?",
            [
                { label: "Move", value: "move" },
                { label: "Change Type", value: "change" },
            ],
            false,
        )
        .required();

    if (isMove) {
        actionSchemaBase = actionSchemaBase
            .custom("categoryID", {
                type: "object",
                "x-control": {
                    label: "Category",
                    description: "Select a new category to move this post to.",
                    inputType: "custom",
                    component: CategoryDropdown,
                    componentProps: {
                        initialValues: discussion.categoryID,
                        value: formValues.categoryID,
                    },
                },
            })
            .required()
            .selectStatic("postTypeID", "Post Type", "Select a new post type to change this post to.", postTypeOptions)
            .required()
            .checkBox("redirect", "Leave a redirect link?");
    } else {
        actionSchemaBase = actionSchemaBase
            .selectStatic("postTypeID", "Post Type", "Select a new post type to change this post to.", postTypeOptions)
            .required()
            .custom("categoryID", {
                type: "object",
                "x-control": {
                    label: "Category",
                    description: "Select a new category to move this post to.",
                    inputType: "custom",
                    component: CategoryDropdown,
                    componentProps: {
                        initialValues: discussion.categoryID,
                        value: formValues.categoryID,
                    },
                },
            })
            .required();
    }

    return actionSchemaBase.getSchema();
}

// Common frame header hook
function useFrameTitle(discussion: IDiscussion, formValues: IPostSettingsFormValues): IFrameTitle {
    const isMove = formValues.action === "move";
    const actionWillMovePost = formValues.categoryID !== discussion.categoryID;
    const isChangeType = formValues.postTypeID !== (discussion?.postTypeID ?? discussion?.type);

    const title = isMove && isChangeType ? "Move and Change Type" : isMove ? "Move" : "Change Type";

    const finalizeLabel = isChangeType && isMove ? t("Save") : isChangeType ? t("Change Type") : t("Move");

    return {
        title: (
            <>
                {t(title)}
                {' "' + discussion.name + '"'}
            </>
        ),
        finalizeLabel,
        isMove,
        actionWillMovePost,
        isChangeType,
    };
}

// Simple version for when POST_TYPES_ENABLED is false
function SimplePostSettingsImpl(props: IPostSettingsImplProps) {
    const { onClose, handleSuccess, discussion, initialAction } = props;
    const { formValues, setFormValues } = usePostSettingsFormState(discussion, initialAction);
    const selectedCategory = useCategory(formValues.categoryID);

    // Get post types from meta instead of API
    const postTypesFromMeta = getMeta("postTypes", []) as string[];
    const allPostTypeOptions: NestedSelect.Option[] = postTypesFromMeta.map((postType: string) => ({
        label: labelize(postType),
        value: postType,
    }));

    const allowedDiscussionTypesInSelectedCategory = selectedCategory.data?.allowedDiscussionTypes ?? [];

    // Use the common validation hook
    const validation = usePostTypeFieldValidation(
        selectedCategory.data,
        allowedDiscussionTypesInSelectedCategory,
        formValues,
    );

    // Filter post type options based on what's allowed in the selected category
    let allowedPostTypeOptions = allPostTypeOptions.filter((option) =>
        allowedDiscussionTypesInSelectedCategory.includes(String(option.value)),
    );

    // Add current discussion type if not already in allowed list
    const discussionPostType = discussion?.postTypeID ?? discussion?.type ?? "discussion";
    const discussionTypeOption = allPostTypeOptions.find((option) => option.value === discussionPostType);
    allowedPostTypeOptions =
        discussionTypeOption && !allowedPostTypeOptions.find((option) => option.value === discussionPostType)
            ? [discussionTypeOption, ...allowedPostTypeOptions]
            : allowedPostTypeOptions;

    // If we're changing type, we need to show all post types, not just the allowed ones
    // The user will then have to select a new category if they want to change the type to one that's not allowed in the current category
    const finalPostTypeOptions = formValues.action === "change" ? allPostTypeOptions : allowedPostTypeOptions;

    const { title, finalizeLabel } = useFrameTitle(discussion, formValues);
    const actionSchema = usePostSettingsSchema(discussion, formValues, finalPostTypeOptions);
    const classes = postSettingsFormClasses();

    const mutation = usePostSettingsMutation(discussion, onClose, handleSuccess);

    const handleSubmit = () => {
        mutation.mutate({
            action: formValues.action,
            categoryID: formValues.categoryID,
            postTypeID: formValues.postTypeID,
            addRedirects: formValues.redirect,
        });
    };

    const formRef = useRef<HTMLFormElement>(null);

    let submitButton = (
        <Button submit buttonType={"textPrimary"} mutation={mutation} disabled={!!validation.disableNextReason}>
            {finalizeLabel}
        </Button>
    );

    if (validation.disableNextReason) {
        submitButton = (
            <ToolTip label={validation.disableNextReason}>
                <span>{submitButton}</span>
            </ToolTip>
        );
    }

    return (
        <ErrorBoundary>
            <form
                ref={formRef}
                onSubmit={(e) => {
                    e.preventDefault();
                    handleSubmit();
                }}
            >
                <Frame
                    header={<FrameHeader title={title} closeFrame={onClose} />}
                    body={
                        <FrameBody className={classes.modal.maxHeight}>
                            {mutation.error && (
                                <>
                                    <Message
                                        className={classes.requiredWarning}
                                        type={"error"}
                                        error={mutation.error as IError}
                                    />
                                </>
                            )}
                            <DashboardSchemaForm
                                key={stableObjectHash(actionSchema)}
                                onChange={setFormValues}
                                schema={actionSchema}
                                instance={formValues}
                                fieldErrors={validation.fieldErrors}
                            />
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter justifyRight={true}>
                            <Button onClick={onClose} buttonType={"text"}>
                                {t("Cancel")}
                            </Button>

                            {submitButton}
                        </FrameFooter>
                    }
                />
            </form>
        </ErrorBoundary>
    );
}

// Complex version for when POST_TYPES_ENABLED is true
function CustomPostTypesSettingsImpl(props: IPostSettingsImplProps) {
    const { onClose, handleSuccess, discussion, initialAction } = props;
    const { formValues: actionFormValues, setFormValues: setActionFormValues } = usePostSettingsFormState(
        discussion,
        initialAction,
    );

    const [currentStep, setCurrentStep] = useState<PostSettingsSteps>("action");
    const [steps, setSteps] = useState<PostSettingsSteps[]>(["action", "summary"]);

    const initialPostFieldMap: Record<PostFieldMap["currentField"], PostFieldMap> = Object.entries(
        discussion.postMeta ?? {},
    ).reduce((acc, [key, value]) => {
        return {
            ...acc,
            [key]: {
                currentField: key,
                targetField: undefined,
                currentFieldValue: value,
                targetFieldValue: undefined,
            },
        };
    }, {});

    const [postFieldMap, setPostFieldMap] =
        useState<Record<PostFieldMap["currentField"], PostFieldMap>>(initialPostFieldMap);

    const allPostTypeQuery = usePostTypeQuery({ expand: ["all"] });
    const selectedCategory = useCategory(actionFormValues.categoryID);

    const allowedPostTypesInSelectedCategory = selectedCategory.data?.allowedPostTypeIDs ?? [];

    // Use the common validation hook
    const validation = usePostTypeFieldValidation(
        selectedCategory.data,
        allowedPostTypesInSelectedCategory,
        actionFormValues,
    );

    const targetPostType = allPostTypeQuery?.data?.find(
        (postType) => postType.postTypeID === actionFormValues.postTypeID,
    );
    const discussionPostTypeID = discussion?.postTypeID ?? discussion?.type ?? "discussion";
    const discussionPostType = allPostTypeQuery?.data?.find((postType) => postType.postTypeID === discussionPostTypeID);
    const postTypeOptionsFromCategory = selectedCategory.data?.allowedPostTypeOptions;

    const availablePostTypeOptions =
        discussionPostType && postTypeOptionsFromCategory
            ? [discussionPostType, ...(postTypeOptionsFromCategory ?? [])]?.map((postType) => ({
                  label: postType.name,
                  value: postType.postTypeID,
              }))
            : [];

    const allPostTypeOptions: NestedSelect.Option[] =
        allPostTypeQuery?.data
            ?.filter((postType) => postType.isActive)
            ?.map((postType) => ({
                label: postType.name,
                value: postType.postTypeID,
            })) ?? [];

    const { title, finalizeLabel, isMove, isChangeType } = useFrameTitle(discussion, actionFormValues);

    const postTypeOptions = isMove ? availablePostTypeOptions : allPostTypeOptions;
    const actionSchema = usePostSettingsSchema(
        discussion,
        actionFormValues,
        postTypeOptions.filter((option) => option.value !== "poll"),
    );

    const isAnyLoading = [allPostTypeQuery.isLoading, selectedCategory.isLoading].some((loading) => loading);

    useEffect(() => {
        // If our current step just moved to field mappings we should have our new and old post types available.
        // Let's wire up automatic mappings for fields that are the SAME between the two post types.
        if (currentStep === "remap") {
            const currentTypeFields = discussionPostType?.postFields ?? [];
            const targetTypeFields = targetPostType?.postFields ?? [];

            const fieldsNeedingMapping = currentTypeFields.length > 0 || targetTypeFields.length > 0;

            if (fieldsNeedingMapping) {
                const fieldsToMap = currentTypeFields.filter((field) =>
                    targetTypeFields.some((targetField) => targetField.postFieldID === field.postFieldID),
                );
                const newPostFieldMap: Record<PostFieldMap["currentField"], PostFieldMap> = fieldsToMap.reduce(
                    (acc, field) => {
                        return {
                            ...acc,
                            [field.postFieldID]: {
                                currentField: field.postFieldID,
                                targetField: field.postFieldID,
                                currentFieldValue: discussion.postMeta?.[field.postFieldID],
                                targetFieldValue: discussion.postMeta?.[field.postFieldID],
                            },
                        };
                    },
                    {} as Record<PostFieldMap["currentField"], PostFieldMap>,
                );

                setPostFieldMap(newPostFieldMap);
            }
        }
    }, [currentStep, discussionPostType, targetPostType]);

    useEffect(() => {
        const steps: PostSettingsSteps[] = ["action"];

        if (isChangeType) {
            // Check that the current Type and Target type both have fields needing mapping
            const currentTypeFields = discussionPostType?.postFields ?? [];
            const targetTypeFields = targetPostType?.postFields ?? [];
            const currentHasFields = currentTypeFields.length > 0;
            const targetHasFields = targetTypeFields.length > 0;

            if (currentHasFields) {
                steps.push("remap");
            }

            if (targetHasFields) {
                steps.push("fieldValidation");
            }
        }
        steps.push("summary");

        setSteps(steps);
    }, [isChangeType, targetPostType]);

    let content = <></>;

    const mutation = usePostSettingsMutation(discussion, onClose, handleSuccess);

    switch (currentStep) {
        case "action":
            content = (
                <DashboardSchemaForm
                    key={stableObjectHash(actionSchema)}
                    onChange={setActionFormValues}
                    schema={actionSchema}
                    instance={actionFormValues}
                    fieldErrors={validation.fieldErrors}
                />
            );
            break;
        case "remap":
            if (discussionPostType && targetPostType) {
                content = (
                    <PostSettingsFieldMapper
                        discussion={discussion}
                        currentPostType={discussionPostType}
                        targetPostType={targetPostType}
                        postFieldMap={postFieldMap}
                        setPostFieldMap={(updatedMap) => {
                            setPostFieldMap({
                                ...postFieldMap,
                                [updatedMap.currentField]: updatedMap,
                            });
                        }}
                    />
                );
            } else {
                content = <div>{t("There has been an error")}</div>;
            }
            break;
        case "fieldValidation":
            if (targetPostType) {
                content = (
                    <PostSettingsFieldValidation
                        fieldErrors={
                            mutation.error && typeof mutation.error === "object" && "errors" in mutation.error
                                ? (mutation.error?.errors as any)
                                : undefined
                        }
                        targetPostType={targetPostType}
                        postFieldMap={postFieldMap}
                        setPostFieldMap={(updatedMap) => {
                            setPostFieldMap({
                                ...postFieldMap,
                                [updatedMap.currentField]: updatedMap,
                            });
                        }}
                    />
                );
            } else {
                content = <div>{t("There has been an error")}</div>;
            }
            break;
        case "summary":
            content = (
                <PostSettingChangeSummary
                    discussion={discussion}
                    targetCategory={selectedCategory.data}
                    redirect={actionFormValues.redirect}
                    currentPostType={discussionPostType}
                    targetPostType={targetPostType}
                    postFieldMap={postFieldMap}
                />
            );
            break;
        default:
            content = <></>;
    }

    const classes = postSettingsFormClasses();

    const handleSubmit = async () => {
        const postFieldMapByTargetField = Object.values(postFieldMap).reduce((acc, curr) => {
            return {
                ...acc,
                [curr.targetField]: curr,
            };
        }, {} as Record<string, PostFieldMap>);

        const postMeta = Object.values(targetPostType?.postFields ?? []).reduce((acc, field) => {
            const postFieldID = postFieldMapByTargetField?.[`${field.postFieldID}`]?.targetField;
            const type = targetPostType?.postFields.find((field) => field.postFieldID === postFieldID)?.formType;

            const value =
                type === "tokens"
                    ? postFieldMapByTargetField?.[`${field.postFieldID}`]?.targetFieldValue
                    : String(postFieldMapByTargetField?.[`${field.postFieldID}`]?.targetFieldValue ?? "");

            return {
                ...acc,
                ...(postFieldMapByTargetField?.[`${field.postFieldID}`]?.targetFieldValue && {
                    [field.postFieldID]: value,
                }),
            };
        }, {} as Record<string, string | string[]>);

        try {
            await mutation.mutateAsync({
                action: actionFormValues.action,
                categoryID: actionFormValues.categoryID,
                postTypeID: actionFormValues.postTypeID,
                postMeta: isChangeType ? postMeta : undefined,
                addRedirects: actionFormValues.redirect,
            });
            if (props.isLegacyPage) {
                window.location.reload();
            }
        } catch (error) {
            if (steps.includes("fieldValidation")) {
                setCurrentStep("fieldValidation");
            }
        }
    };

    const formRef = useRef<HTMLFormElement>(null);

    return (
        <ErrorBoundary>
            <form ref={formRef} onSubmit={(e) => e.preventDefault()}>
                <Frame
                    header={<FrameHeader title={title} closeFrame={onClose} />}
                    body={
                        <FrameBody className={classes.modal.maxHeight}>
                            {mutation.error && (
                                <>
                                    <Message
                                        className={classes.requiredWarning}
                                        type={"error"}
                                        error={mutation.error as IError}
                                    />
                                    {typeof mutation.error === "object" &&
                                        "errors" in mutation.error &&
                                        Array.isArray(mutation.error.errors) && (
                                            <ErrorMessages
                                                className={classes.errorMessages}
                                                errors={Object.values(mutation.error.errors).flat()}
                                            />
                                        )}
                                </>
                            )}
                            {isAnyLoading ? <FormSkeleton numberOfRows={3} /> : content}
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter justifyRight={true}>
                            <SteppedModalFooter
                                isFirstStep={currentStep === "action"}
                                isFinalStep={currentStep === "summary"}
                                onBack={() => {
                                    const previousStepIndex = steps.indexOf(currentStep) - 1;
                                    if (previousStepIndex >= 0) {
                                        setCurrentStep(steps[previousStepIndex]);
                                    }
                                }}
                                onNext={() => {
                                    if (!formRef.current?.reportValidity()) {
                                        return;
                                    }

                                    const nextStepIndex = steps.indexOf(currentStep) + 1;
                                    if (nextStepIndex <= steps.length - 1) {
                                        setCurrentStep(steps[nextStepIndex]);
                                    }
                                }}
                                onCancel={onClose}
                                finalizeLabel={finalizeLabel}
                                onFinalize={() => handleSubmit()}
                                loading={mutation.isLoading}
                                disable={mutation.isLoading}
                                disableNextReason={
                                    validation.disableNextReason ?? (isAnyLoading ? t("Loading...") : undefined)
                                }
                            />
                        </FrameFooter>
                    }
                />
            </form>
        </ErrorBoundary>
    );
}

function PostSettingsImpl(props: IPostSettingsImplProps) {
    if (POST_TYPES_ENABLED) {
        return <CustomPostTypesSettingsImpl {...props} />;
    } else {
        return <SimplePostSettingsImpl {...props} />;
    }
}

export default PostSettingsImpl;
