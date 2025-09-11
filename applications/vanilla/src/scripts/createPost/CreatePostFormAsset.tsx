/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { usePostTypeQuery } from "@dashboard/postTypes/postType.hooks";
import { PostType } from "@dashboard/postTypes/postType.types";
import { useCategoryList } from "@library/categoriesWidget/CategoryList.hooks";
import Translate from "@library/content/Translate";
import { TagPostUI } from "@library/features/discussions/forms/TagPostUI";
import { IPermissionOptions, PermissionMode } from "@library/features/users/Permission";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { FormControl, FormControlGroup } from "@library/forms/FormControl";
import InputBlock from "@library/forms/InputBlock";
import { NestedSelect } from "@library/forms/nestedSelect";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { ErrorIcon } from "@library/icons/common";
import { IFieldError, IPickerOption, JsonSchemaForm } from "@library/json-schema-forms";
import Message from "@library/messages/Message";
import { getMeta, safelyParseJSON, safelySerializeJSON } from "@library/utility/appUtils";
import { EMPTY_RICH2_BODY } from "@library/vanilla-editor/utils/emptyRich2";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { useDraftContext } from "@vanilla/addon-vanilla/drafts/DraftContext";
import { makePostDraft, mapDraftToPostFormValues } from "@vanilla/addon-vanilla/drafts/utils";
import { FilteredCategorySelector } from "@vanilla/addon-vanilla/createPost/FilteredCategorySelector";
import { createPostFormAssetClasses } from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset.classes";
import { ICreatePostForm, usePostMutation } from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset.hooks";
import { buildSchemaFromPostFields, getPostEndpointForPostType } from "@vanilla/addon-vanilla/createPost/utils";
import { t } from "@vanilla/i18n";
import { useEffect, useMemo, useRef, useState } from "react";
import { notEmpty, RecordID } from "@vanilla/utils";
import isEqual from "lodash-es/isEqual";
import { CreatePostFormAssetSkeleton } from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset.skeleton";
import { useParentRecordContext } from "@vanilla/addon-vanilla/posts/ParentRecordContext";
import { RadioGroupContext } from "@library/forms/RadioGroupContext";
import RadioButton from "@library/forms/RadioButton";
import { UseQueryResult } from "@tanstack/react-query";
import { useLayoutQueryContext } from "@library/features/Layout/LayoutQueryProvider";
import { ILayoutQuery } from "@library/features/Layout/LayoutRenderer.types";
import { CreatePostParams, DraftStatus, EditExistingPostParams } from "@vanilla/addon-vanilla/drafts/types";
import { IDraft } from "@vanilla/addon-vanilla/drafts/types";
import { DraftsApi } from "@vanilla/addon-vanilla/drafts/DraftsApi";
import DraftFormFooterContent from "@vanilla/addon-vanilla/drafts/components/DraftFormFooterContent";
import { getSiteSection } from "@library/utility/appUtils";
import InputTextBlock from "@library/forms/InputTextBlock";
import { useLinkContext } from "@library/routing/links/LinkContextProvider";
import ErrorMessages from "@library/forms/ErrorMessages";
import { useToast } from "@library/features/toaster/ToastContext";

interface IProps {
    category: ICategory | null;
    postType?: PostType;
    isPreview?: boolean;
    title?: string;
    description?: string;
    subtitle?: string;
    containerOptions?: IHomeWidgetContainerOptions;
    taggingEnabled?: boolean;
}

interface IGroup {
    groupID: RecordID;
    name: string;
    url: string;
    dateInserted: string;
    dateUpdated: string;
    categoryID: RecordID;
    countDiscussions: number;
    iconUrl?: string;
    dateFollowed?: string;
}

const BASE_PERMISSIONS_OPTIONS: IPermissionOptions = {
    resourceType: "category",
    mode: PermissionMode.RESOURCE_IF_JUNCTION,
};

const INITIAL_EMPTY_FORM_BODY: Partial<ICreatePostForm> = {
    name: "",
    format: "rich2",
    body: EMPTY_RICH2_BODY,
    postTypeID: undefined,
    categoryID: undefined,
    pinLocation: "none",
    pinned: false,
};

/**
 * A form for creating a new post
 */
export function CreatePostFormAsset(props: IProps) {
    const { addToast } = useToast();
    const { pushSmartLocation } = useLinkContext();
    const { hasPermission } = usePermissionsContext();
    // remove feature flag dependency when this feature is fully released
    const canScheduleDraft = getMeta("featureFlags.DraftScheduling.Enabled", false) && hasPermission("schedule.allow");

    // Do some look up from the URL
    const { layoutQuery } = useLayoutQueryContext();
    const { params } = layoutQuery as ILayoutQuery<CreatePostParams | EditExistingPostParams>;
    const parentRecordContext = useParentRecordContext<UseQueryResult<IGroup, any>>();
    const isEdit = !!parentRecordContext?.record;

    const initialPostID = `${parentRecordContext.recordID}`.length > 0 ? parentRecordContext.recordID ?? "0" : "0";

    const classes = createPostFormAssetClasses.useAsHook();
    const { category, postType, taggingEnabled = false } = props;
    const userCanCreateNewTags = taggingEnabled && (hasPermission("tags.add") || isEdit);

    const formRef = useRef<HTMLFormElement | null>(null);

    const initialFormBody: Partial<ICreatePostForm> = {
        // Empty form body
        ...INITIAL_EMPTY_FORM_BODY,
        // If we get values from the props
        ...(category && category.categoryID !== -1 && { categoryID: category.categoryID }),
        ...(postType && postType.postTypeID && { postTypeID: postType.postTypeID }),
        ...(parentRecordContext.recordType && { postTypeID: parentRecordContext.recordType }),
        // If we get values from the parent record context
        ...(parentRecordContext?.record && {
            categoryID: parentRecordContext.record.categoryID,
            ...(parentRecordContext.record.postTypeID && { postTypeID: parentRecordContext.record.postTypeID }),
            postMeta: parentRecordContext.record.postMeta,
            name: parentRecordContext.record.name,
            body: parentRecordContext.record.body,
            format: parentRecordContext.record.format,
            pinLocation: parentRecordContext.record.pinLocation ?? "none",
            pinned: parentRecordContext.record.pinned ?? false,
            ...(taggingEnabled && {
                tagIDs: parentRecordContext.record.tagIDs ?? [],
                ...(userCanCreateNewTags && {
                    newTagNames: parentRecordContext.record.newTagNames ?? [],
                }),
            }),
        }),
        ...(parentRecordContext.record &&
            parentRecordContext.record.format.toLowerCase() === "rich2" && {
                body:
                    typeof parentRecordContext.record.body === "string" &&
                    safelyParseJSON(parentRecordContext.record.body),
            }),
    };

    const needsConversion = parentRecordContext.record && parentRecordContext.record.format.toLowerCase() !== "rich2";

    const { draftID, draft, draftLoaded, updateDraft, updateImmediate, enableAutosave, disableAutosave, removeDraft } =
        useDraftContext();

    // Handle group information
    // GroupID can come from one of 3 places:
    // The parent record (if editing a post)
    // The layout params (if creating a new post in a group)
    // The draft (if editing a draft of a group post)
    const groupID = useMemo<RecordID | null>(() => {
        if (parentRecordContext?.record?.groupID) {
            return parentRecordContext.record.groupID;
        }
        if (params?.parentRecordType === "group" && params?.parentRecordID) {
            return params.parentRecordID;
        }
        if (draft && draft?.attributes?.groupID) {
            return draft.attributes.groupID;
        }
        return null;
    }, [parentRecordContext?.record?.groupID, params, draft]);

    const groupLookup = parentRecordContext.getExternalData("fetchGroupByID", [{ groupID }, !!groupID]);

    const [formBody, setFormBody] = useState<Partial<ICreatePostForm>>(initialFormBody);

    useEffect(() => {
        if (groupID && groupLookup?.data && formBody.groupID !== groupLookup?.data?.groupID) {
            updateFormBody({ groupID: groupLookup?.data?.groupID, categoryID: groupLookup?.data?.categoryID });
        }
    }, [groupLookup]);

    const postMutation = usePostMutation();

    const moderationMessage = postMutation.data?.status === 202 ? postMutation.data?.message : undefined;
    const [error, setError] = useState<string>();
    const [fieldErrors, setFieldErrors] = useState<IFieldError[] | null>(null);

    useEffect(() => {
        if (postMutation.error) {
            setError(postMutation.error.message);
            setFieldErrors(postMutation.error?.errors?.body ?? null);
        }
    }, [postMutation.error]);

    useEffect(() => {
        if (error) {
            window.scrollTo({ top: 0, behavior: "smooth" });
        }
    }, [error]);

    const allPostTypes = usePostTypeQuery({
        isActive: true,
        expand: ["postFields"],
    });
    const selectedPostType = allPostTypes.data?.find((postType) => postType.postTypeID === formBody.postTypeID);

    const categoryQuery = useCategoryList({ categoryID: formBody.categoryID }, !!formBody?.categoryID);
    const { result } = categoryQuery.data ?? {};
    const selectedCategory = result?.[0] ?? category;

    const schema = useMemo(() => {
        if (selectedPostType?.postFields) {
            return buildSchemaFromPostFields(selectedPostType?.postFields);
        }
        return null;
    }, [selectedPostType]);

    const permissionCategoryID = useMemo(() => {
        if (groupLookup?.data) {
            return groupLookup?.data?.categoryID;
        }
        return formBody.categoryID ?? category?.categoryID ?? null;
    }, [category, formBody.categoryID, formBody.groupID]);

    const canAnnounce = permissionCategoryID
        ? hasPermission("discussions.announce", { ...BASE_PERMISSIONS_OPTIONS, resourceID: +permissionCategoryID })
        : null;
    const announcementOptions: IPickerOption[] | null =
        selectedCategory && canAnnounce
            ? [
                  {
                      label: "Don't announce",
                      value: "none",
                  },
                  {
                      label: <Translate source={"Announce in <0/>"} c0={selectedCategory.name} />,
                      value: "category",
                  },
                  ...(canAnnounce
                      ? [
                            {
                                label: (
                                    <Translate
                                        source={"Announce in  <0/> and recent posts"}
                                        c0={selectedCategory.name}
                                    />
                                ),
                                value: "recent",
                            },
                        ]
                      : []),
              ]
            : null;

    const isScheduledDraft = !!draft && !!draft.dateScheduled;
    useEffect(() => {
        if (isScheduledDraft) {
            disableAutosave();
        }
    }, [isScheduledDraft]);

    const siteSectionID = getSiteSection()?.sectionID;

    // We don't need to filter if we're not in a subcommunity (indicated by siteSectionID of "0")
    const filterCategoriesBySiteSection = siteSectionID !== "0";

    const categoriesForCurrentSiteSection = useCategoryList(
        { siteSectionID: siteSectionID, includeParentCategory: 1 },
        filterCategoriesBySiteSection,
    );

    let allowedPostTypesForCurrentSiteSection: string[] = [];
    categoriesForCurrentSiteSection?.data?.result &&
        categoriesForCurrentSiteSection.data.result.forEach(
            (category) =>
                (allowedPostTypesForCurrentSiteSection = allowedPostTypesForCurrentSiteSection.concat(
                    category.allowedPostTypeIDs ?? [],
                )),
        );

    const postTypeOptions = useMemo(() => {
        if (!formBody?.categoryID) {
            const allowedPostTypesData = filterCategoriesBySiteSection
                ? allPostTypes.data?.filter((postType) =>
                      allowedPostTypesForCurrentSiteSection.includes(postType.postTypeID),
                  )
                : allPostTypes.data;

            return allowedPostTypesData?.map((postType) => {
                return {
                    label: postType.name,
                    value: postType.postTypeID,
                };
            });
        }
        return selectedCategory?.allowedPostTypeOptions?.map((postType) => {
            return {
                label: postType.name,
                value: postType.postTypeID,
            };
        });
    }, [selectedCategory, allPostTypes, formBody]);

    const updateDraftField = (updatedField: Record<string, any>) => {
        // Turn the current draft into form values
        const draftAsFormValues = (draft && mapDraftToPostFormValues(draft)) ?? initialFormBody;
        // We compare the updated field with the current draft, if they do not match, we update the draft
        const shouldUpdate = Object.keys(updatedField).some((key) => {
            return !isEqual(updatedField[key], draftAsFormValues?.[key]);
        });

        // No autosave if scheduled draft
        if (shouldUpdate && !isScheduledDraft) {
            disableAutosave();
            const draftPayload = makePostDraft({
                ...formBody,
                ...updatedField,
                ...(isEdit && initialPostID !== "0" && { recordID: initialPostID }),
            });
            updateDraft(draftPayload);
            enableAutosave();
        }
    };

    const updateFormBody = (updatedField: Record<string, any>) => {
        updateDraftField(updatedField);
        // Here we set the form body state, updatedField can modify or add new fields
        setFormBody((prev) => ({
            ...prev,
            ...updatedField,
        }));
    };

    useEffect(() => {
        // We now have a server draftID and we need to update the URL
        if (draftID) {
            window.history.replaceState(
                null,
                "",
                `${getMeta("context.basePath", "")}/post/editdiscussion/${initialPostID}/${draftID}`,
            );
        }
    }, [draftID]);

    const handleSubmit = async () => {
        // Disable draft autosave
        disableAutosave();
        const endpoint = getPostEndpointForPostType(selectedPostType ?? null);
        const { pinLocation, pinned, ...rest } = formBody;
        const body = {
            ...rest,
            body: safelySerializeJSON(formBody.body) ?? "",
            ...(formBody.format !== "rich2" && { format: "rich2" }),
            ...(canAnnounce && {
                ...(pinLocation === "none" && { pinned: false, pinLocation: undefined }),
                ...(pinLocation !== "none" && { pinned: true, pinLocation }),
            }),
            ...(draftID && { draftID: draftID }),
            ...(initialPostID && initialPostID !== "0" && { discussionID: initialPostID }),
        } as ICreatePostForm;

        if (endpoint) {
            try {
                const response = await postMutation.mutateAsync({
                    endpoint,
                    body,
                });

                removeDraft(true);

                const responseHasMessage = "status" in response && "message" in response;
                if (!responseHasMessage) {
                    addToast({
                        autoDismiss: true,
                        body: body.discussionID ? t("Success! Post updated") : t("Success! Post created"),
                    });

                    pushSmartLocation(response.canonicalUrl);
                }
            } catch (error) {
                addToast({
                    autoDismiss: false,
                    dismissible: true,
                    body: t("Error. Post could not be created."),
                });
                // Re-enable autosave
                enableAutosave();
            }
        }
    };

    // We want to wait for the draft to populate the form
    // But only do it once so we don't overwrite any user changes
    const initialDraftLoaded = useRef<boolean>();
    useEffect(() => {
        if (draft && !initialDraftLoaded.current) {
            const formValuesFromDraft = mapDraftToPostFormValues(draft);
            const { tagIDs, newTagNames, ...rest } = formValuesFromDraft ?? {};
            setFormBody((prev) => ({
                ...prev,
                ...rest,
                ...(taggingEnabled && {
                    tagIDs: tagIDs ?? [],
                    ...(userCanCreateNewTags && {
                        newTagNames: newTagNames ?? [],
                    }),
                }),
            }));
            initialDraftLoaded.current = true;
        }
    }, [draftLoaded]);

    const formLoaded =
        allPostTypes.isSuccess &&
        (formBody?.categoryID ? categoryQuery.isSuccess : true) &&
        (groupID ? groupLookup?.isSuccess : true);

    // if errored schedule, trigger form validation
    const isErroredSchedule = !!draft && (draft as IDraft).draftStatus === DraftStatus.ERROR;
    useEffect(() => {
        if (isErroredSchedule && formLoaded) {
            // wait a bit so dropdowns can be populated
            setTimeout(() => {
                if (!formRef.current?.checkValidity()) {
                    formRef.current?.reportValidity();
                }
            }, 500);
        }
    }, [isErroredSchedule, formLoaded]);

    const manualDraftSave = async (newScheduleParams?: Partial<DraftsApi.PatchParams>) => {
        disableAutosave();
        const scheduleParams = newScheduleParams
            ? newScheduleParams
            : isScheduledDraft && draft.dateScheduled
            ? { dateScheduled: draft?.dateScheduled, draftStatus: draft?.draftStatus }
            : {};
        const draftPayload = makePostDraft({
            ...formBody,
            ...scheduleParams,
            ...(isEdit && initialPostID !== "0" && { recordID: initialPostID }),
        });

        // we need to check form validity in case this is scheduled draft
        const isValidForm = isScheduledDraft ? formRef.current?.checkValidity() : true;

        if (isValidForm) {
            await updateImmediate(draftPayload);
        } else {
            formRef.current?.reportValidity();
        }
        !isScheduledDraft && enableAutosave();
    };

    const isBodyRequired = getMeta("posting.minLength", 0) > 0;

    const postTitleMaxLength = getMeta("posting.titleMaxLength", 250);

    /**
     * Need to massage the field level errors here because the form
     * does not render postMeta values in side an object named "postMeta"
     * but the API requires it.
     */
    const errorMemo = useMemo(() => {
        if (postMutation.error?.errors) {
            const errorKeys = Object.keys(postMutation.error.errors);
            const processed = errorKeys.reduce((acc, key) => {
                const errorList = postMutation.error.errors?.[key];
                const updated =
                    Array.isArray(errorList) &&
                    errorList.map((error) => {
                        return {
                            ...error,
                            path: undefined,
                            message: error.message.replace("postMeta.", ""),
                        };
                    });
                return { ...acc, [key]: updated };
            }, {});
            return processed;
        }
        return postMutation.error?.errors;
    }, [postMutation.error?.errors]);

    const isSubmitting = postMutation.isLoading;

    if (moderationMessage) {
        return <Message type="neutral" stringContents={moderationMessage} contents={moderationMessage} />;
    }

    return (
        <>
            {formLoaded ? (
                <HomeWidgetContainer
                    title={props.title}
                    description={props.description}
                    subtitle={props.subtitle}
                    options={props.containerOptions}
                >
                    {error && (
                        <Message
                            type="error"
                            stringContents={error}
                            icon={<ErrorIcon />}
                            contents={fieldErrors ? <ErrorMessages errors={fieldErrors} /> : null}
                        />
                    )}

                    <form
                        role="form"
                        ref={formRef}
                        onSubmit={async (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            await handleSubmit();
                        }}
                        style={{ ...(props.isPreview && { pointerEvents: "none" }) }}
                    >
                        <section className={classes.formContainer}>
                            <div className={classes.categoryTypeContainer}>
                                {groupID ? (
                                    <div>
                                        <InputBlock
                                            legend={<label className={classes.labelStyle}>{t("Group")}</label>}
                                            required
                                        >
                                            <NestedSelect
                                                value={groupID}
                                                options={[
                                                    { label: groupLookup?.data?.name ?? "Group", value: groupID },
                                                ]}
                                                onChange={() => {}}
                                                required
                                                disabled
                                            />
                                        </InputBlock>
                                    </div>
                                ) : (
                                    <div>
                                        <InputBlock
                                            legend={<label className={classes.labelStyle}>{t("Category")}</label>}
                                            required
                                        >
                                            {isEdit ? (
                                                <NestedSelect
                                                    value={selectedCategory.categoryID}
                                                    options={[
                                                        {
                                                            label: selectedCategory.name,
                                                            value: selectedCategory.categoryID,
                                                        },
                                                    ]}
                                                    onChange={() => null}
                                                    required
                                                    disabled
                                                />
                                            ) : (
                                                <FilteredCategorySelector
                                                    postTypeID={formBody.postTypeID}
                                                    filterByCurrentSiteSection
                                                    initialValues={
                                                        category?.categoryID !== -1 ? category?.categoryID : undefined
                                                    }
                                                    value={formBody.categoryID}
                                                    onChange={(categoryID: RecordID | undefined) => {
                                                        updateFormBody({ categoryID });
                                                    }}
                                                    isClearable
                                                    required
                                                />
                                            )}
                                        </InputBlock>
                                    </div>
                                )}
                                <div>
                                    <InputBlock
                                        legend={<label className={classes.labelStyle}>{t("Post Type")}</label>}
                                        required
                                    >
                                        <NestedSelect
                                            value={formBody.postTypeID}
                                            options={
                                                !isEdit
                                                    ? postTypeOptions ?? []
                                                    : allPostTypes?.data
                                                          ?.map((postType) => {
                                                              if (postType.postTypeID === formBody.postTypeID) {
                                                                  return {
                                                                      label: postType.name,
                                                                      value: postType.postTypeID,
                                                                  };
                                                              }
                                                          })
                                                          .filter(notEmpty) ?? []
                                            }
                                            onChange={(postTypeID: string | undefined) => {
                                                !isEdit && updateFormBody({ postTypeID });
                                            }}
                                            isClearable={!isEdit}
                                            disabled={isEdit}
                                            required
                                        />
                                    </InputBlock>
                                </div>
                            </div>
                            <div>
                                <InputTextBlock
                                    label={
                                        <>
                                            {selectedPostType ? (
                                                <Translate source={"<0/> Title"} c0={selectedPostType.name} />
                                            ) : (
                                                <>{t("Title")}</>
                                            )}
                                        </>
                                    }
                                    required
                                    inputProps={{
                                        value: formBody.name,
                                        onChange: (event) => {
                                            updateFormBody({ name: event.currentTarget.value });
                                        },
                                        required: true,
                                        maxLength: postTitleMaxLength,
                                    }}
                                />
                            </div>
                            <div className={classes.main}>
                                <div className={classes.postFieldsContainer}>
                                    {schema && (
                                        <JsonSchemaForm
                                            FormControl={FormControl}
                                            FormControlGroup={FormControlGroup}
                                            schema={schema}
                                            instance={formBody.postMeta}
                                            onChange={(valueDispatch) => {
                                                updateFormBody({
                                                    postMeta: { ...formBody.postMeta, ...valueDispatch() },
                                                });
                                            }}
                                            fieldErrors={errorMemo}
                                        />
                                    )}
                                </div>
                                <div className={classes.postBodyContainer}>
                                    <InputBlock
                                        legend={<label className={classes.labelStyle}>{t("Body")}</label>}
                                        label={t("Body")}
                                        required={isBodyRequired}
                                    >
                                        <VanillaEditor
                                            showConversionNotice={needsConversion}
                                            initialContent={formBody.body}
                                            onChange={(body) => updateFormBody({ body })}
                                        />
                                    </InputBlock>
                                </div>
                                {taggingEnabled && (
                                    <div className={classes.tagsContainer}>
                                        <InputBlock
                                            legend={<label className={classes.labelStyle}>{t("Tags")}</label>}
                                            label={t("Tag")}
                                        >
                                            <TagPostUI
                                                initialTags={[
                                                    ...(formBody.tagIDs ?? []),
                                                    ...(formBody.newTagNames ?? []),
                                                ]}
                                                onSelectedExistingTag={(tagIDs) => {
                                                    updateFormBody({ tagIDs });
                                                }}
                                                onSelectedNewTag={(newTagNames) => {
                                                    updateFormBody({ newTagNames });
                                                }}
                                                popularTagsLayoutClasses={classes.popularTagsLayout}
                                                popularTagsTitle={
                                                    <span className={classes.labelStyle}>{t("Popular Tags")}</span>
                                                }
                                                showPopularTags
                                            />
                                        </InputBlock>
                                    </div>
                                )}
                                {announcementOptions && formBody?.categoryID !== -1 && (
                                    <div className={classes.announcementContainer}>
                                        <InputBlock
                                            legend={<label className={classes.labelStyle}>{t("Announce Post")}</label>}
                                            label={t("Announce Post")}
                                        >
                                            <RadioGroupContext.Provider
                                                value={{
                                                    value: formBody.pinLocation,
                                                    onChange: (
                                                        pinLocation: NonNullable<ICreatePostForm["pinLocation"]>,
                                                    ) => updateFormBody({ pinLocation }),
                                                }}
                                            >
                                                {announcementOptions.map((option) => (
                                                    <RadioButton
                                                        key={option.value}
                                                        label={option.label}
                                                        value={option.value}
                                                    />
                                                ))}
                                            </RadioGroupContext.Provider>
                                        </InputBlock>
                                    </div>
                                )}
                            </div>
                            <DraftFormFooterContent
                                onOpenScheduleModal={() => {
                                    disableAutosave();
                                    setError(undefined);
                                }}
                                handleSaveDraft={manualDraftSave}
                                formRef={formRef}
                                draftSchedulingEnabled={canScheduleDraft}
                                isDraftForExistingRecord={isEdit}
                                isScheduledDraft={isScheduledDraft}
                                recordType="post"
                                submitDisabled={isSubmitting}
                            />
                        </section>
                    </form>
                </HomeWidgetContainer>
            ) : (
                <CreatePostFormAssetSkeleton
                    title={props.title}
                    description={props.description}
                    subtitle={props.subtitle}
                    containerOptions={props.containerOptions}
                />
            )}
        </>
    );
}

export default CreatePostFormAsset;
