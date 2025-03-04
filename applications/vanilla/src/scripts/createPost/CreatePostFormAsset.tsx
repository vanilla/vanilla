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
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { FormControl, FormControlGroup } from "@library/forms/FormControl";
import InputBlock from "@library/forms/InputBlock";
import { NestedSelect } from "@library/forms/nestedSelect";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { ErrorIcon } from "@library/icons/common";
import { IPickerOption, JsonSchemaForm, Select } from "@library/json-schema-forms";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Message from "@library/messages/Message";
import { getMeta, safelyParseJSON, safelySerializeJSON } from "@library/utility/appUtils";
import { EMPTY_RICH2_BODY } from "@library/vanilla-editor/utils/emptyRich2";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { useDraftContext } from "@vanilla/addon-vanilla/drafts/DraftContext";
import { makePostDraft, makePostFormValues } from "@vanilla/addon-vanilla/drafts/utils";
import { FilteredCategorySelector } from "@vanilla/addon-vanilla/createPost/FilteredCategorySelector";
import { createPostFormAssetClasses } from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset.classes";
import { ICreatePostForm, usePostMutation } from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset.hooks";
import { buildSchemaFromPostFields, getPostEndpointForPostType } from "@vanilla/addon-vanilla/createPost/utils";
import { t } from "@vanilla/i18n";
import { TextBox } from "@vanilla/ui";
import { notEmpty, RecordID } from "@vanilla/utils";
import { useEffect, useMemo, useRef, useState } from "react";
import isEqual from "lodash-es/isEqual";
import { CreatePostFormAssetSkeleton } from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset.skeleton";
import DateTime from "@library/content/DateTime";
import { useParentRecordContext } from "@vanilla/addon-vanilla/posts/ParentRecordContext";
import { RadioGroupContext } from "@library/forms/RadioGroupContext";
import RadioButton from "@library/forms/RadioButton";
import { UseQueryResult } from "@tanstack/react-query";
import { useLayoutQueryContext } from "@library/features/Layout/LayoutQueryProvider";
import { ILayoutQuery } from "@library/features/Layout/LayoutRenderer.types";
import { CreatePostParams, EditExistingPostParams } from "@vanilla/addon-vanilla/drafts/types";

interface IProps {
    category: ICategory | null;
    postType?: PostType;
    isPreview?: boolean;
    title?: string;
    description?: string;
    subtitle?: string;
    containerOptions?: IHomeWidgetContainerOptions;
    /** Skip waiting around in tests */
    forceFormLoaded?: boolean;
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
};

/**
 * A form for creating a new post
 */
export function CreatePostFormAsset(props: IProps) {
    // Do some look up from the URL
    const { layoutQuery } = useLayoutQueryContext();
    const { params } = layoutQuery as ILayoutQuery<CreatePostParams | EditExistingPostParams>;
    const parentRecordContext = useParentRecordContext<UseQueryResult<IGroup, any>>();

    const initialPostID = `${parentRecordContext.recordID}`.length > 0 ? parentRecordContext.recordID ?? "0" : "0";

    const classes = createPostFormAssetClasses();
    const { category, postType } = props;

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
        }),
        ...(parentRecordContext.record &&
            parentRecordContext.record.format.toLowerCase() === "rich2" && {
                body:
                    typeof parentRecordContext.record.body === "string" &&
                    safelyParseJSON(parentRecordContext.record.body),
            }),
    };

    const isEdit = !!parentRecordContext?.record;

    const { draftID, draft, draftLoaded, updateDraft, updateImmediate, enable, disable, draftLastSaved } =
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

    const { hasPermission } = usePermissionsContext();

    const [tagsToAssign, setTagsToAssign] = useState<number[]>();
    const [tagsToCreate, setTagsToCreate] = useState<string[]>();
    const [formBody, setFormBody] = useState<Partial<ICreatePostForm>>(initialFormBody);

    useEffect(() => {
        if (groupID && groupLookup?.data && formBody.groupID !== groupLookup?.data?.groupID) {
            updateFormBody({ groupID: groupLookup?.data?.groupID, categoryID: groupLookup?.data?.categoryID });
        }
    }, [groupLookup]);

    const postMutation = usePostMutation(tagsToAssign, tagsToCreate);

    const allPostTypes = usePostTypeQuery({
        postTypeID: formBody.postTypeID,
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

    /**
     * These specific permission sets are used when showing the announce context menu
     * option(canModerate & canAddToCollection).
     * and these when allowing announcing in recent discussion (canAnnounce & canModerate).
     */
    const canAnnounce = permissionCategoryID
        ? hasPermission("discussions.announce", { ...BASE_PERMISSIONS_OPTIONS, resourceID: +permissionCategoryID })
        : null;
    const canModerate = permissionCategoryID
        ? hasPermission("discussions.moderate", { ...BASE_PERMISSIONS_OPTIONS, resourceID: +permissionCategoryID })
        : null;
    const canAddToCollection = hasPermission("community.manage", { mode: PermissionMode.GLOBAL_OR_RESOURCE });

    const announcementOptions: IPickerOption[] | null =
        canModerate || canAddToCollection
            ? [
                  {
                      label: "Don't announce",
                      value: "none",
                  },
                  {
                      label: <Translate source={"Announce in <0/>"} c0={selectedCategory.name} />,
                      value: "category",
                  },
                  ...(canAnnounce || canModerate
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

    useEffect(() => {
        if (canAnnounce) {
            updateFormBody({ pinLocation: "none" });
        }
    }, [canAnnounce, category]);

    const postTypeOptions = useMemo(() => {
        if (!formBody?.categoryID) {
            return allPostTypes.data?.map((postType) => {
                return {
                    label: postType.name,
                    value: postType.postTypeID,
                };
            });
        }
        return selectedCategory.allowedPostTypeOptions?.map((postType) => {
            return {
                label: postType.name,
                value: postType.postTypeID,
            };
        });
    }, [selectedCategory, allPostTypes, formBody]);

    const updateDraftField = (updatedField: Record<string, any>) => {
        // Turn the current draft into form values
        const draftAsFormValues = (draft && makePostFormValues(draft)) ?? initialFormBody;
        // We compare the updated field with the current draft, if they do not match, we update the draft
        const shouldUpdate = Object.keys(updatedField).some((key) => {
            return !isEqual(updatedField[key], draftAsFormValues?.[key]);
        });
        if (shouldUpdate) {
            disable();
            const draftPayload = makePostDraft({
                ...formBody,
                ...updatedField,
            });
            updateDraft(draftPayload);
            enable();
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
        if (tagsToAssign) {
            updateFormBody({ tags: tagsToAssign });
        }
    }, [tagsToAssign]);

    useEffect(() => {
        // We now have a server draftID and we need to update the URL
        // We compare vs the pathname, because local drafts use the pathname as the ID.
        if (draftID && draftID !== window.location.pathname) {
            window.history.replaceState(null, "", `/post/editdiscussion/${initialPostID}/${draftID}`);
        }
    }, [draftID]);

    const handleSubmit = async () => {
        const endpoint = getPostEndpointForPostType(selectedPostType ?? null);
        const body = {
            ...formBody,
            body: safelySerializeJSON(formBody.body) ?? "",
            ...(formBody.format !== "rich2" && { format: "rich2" }),
            ...(formBody.pinLocation === "none" && { pinLocation: undefined }),
            ...(draftID && { draftID: draftID }),
            ...(initialPostID && initialPostID !== "0" && { discussionID: initialPostID }),
        } as ICreatePostForm;
        if (endpoint) {
            postMutation.mutate({
                endpoint,
                body,
            });
        }
    };

    useEffect(() => {
        if (postMutation.error) {
            window.scrollTo({ top: 0, behavior: "smooth" });
        }
    }, [postMutation.error]);

    // We want to wait for the draft to populate the form
    // But only do it once so we don't overwrite any user changes
    const initialDraftLoaded = useRef<boolean>();
    useEffect(() => {
        if (draft && !initialDraftLoaded.current) {
            const formValuesFromDraft = makePostFormValues(draft);
            setFormBody((prev) => ({
                ...prev,
                ...formValuesFromDraft,
            }));
            initialDraftLoaded.current = true;
        }
    }, [draftLoaded]);

    const formLoaded =
        allPostTypes.isSuccess &&
        (formBody?.categoryID ? categoryQuery.isSuccess : true) &&
        (groupID ? groupLookup?.isSuccess : true);

    const [formHasLoaded, setFromHasLoaded] = useState(props.forceFormLoaded ?? false);
    useEffect(() => {
        if (formLoaded) {
            setFromHasLoaded(true);
        }
    }, [formLoaded]);

    const manualDraftSave = async () => {
        disable();
        const draftPayload = makePostDraft(formBody);
        await updateImmediate(draftPayload);
        enable();
    };

    const isBodyRequired = getMeta("posting.minLength", 0) > 0;

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

    return (
        <>
            {formHasLoaded ? (
                <HomeWidgetContainer
                    title={props.title}
                    description={props.description}
                    subtitle={props.subtitle}
                    options={props.containerOptions}
                >
                    {postMutation.error && (
                        <Message type="error" stringContents={postMutation.error.message} icon={<ErrorIcon />} />
                    )}

                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            void handleSubmit();
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
                                <InputBlock
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
                                >
                                    <TextBox
                                        value={formBody.name}
                                        onChange={(event) => {
                                            updateFormBody({ name: event.currentTarget.value });
                                        }}
                                    />
                                </InputBlock>
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
                                            initialContent={formBody.body}
                                            onChange={(body) => updateFormBody({ body })}
                                        />
                                    </InputBlock>
                                </div>
                                <div className={classes.tagsContainer}>
                                    <InputBlock
                                        legend={<label className={classes.labelStyle}>{t("Tags")}</label>}
                                        label={t("Tag")}
                                    >
                                        <TagPostUI
                                            initialTagIDs={formBody.tags}
                                            onSelectedExistingTag={setTagsToAssign}
                                            onSelectedNewTag={setTagsToCreate}
                                            popularTagsLayoutClasses={classes.popularTagsLayout}
                                            popularTagsTitle={
                                                <span className={classes.labelStyle}>{t("Popular Tags")}</span>
                                            }
                                            showPopularTags
                                        />
                                    </InputBlock>
                                </div>
                                {announcementOptions && formBody?.categoryID !== -1 && (
                                    <div className={classes.announcementContainer}>
                                        <InputBlock
                                            legend={<label className={classes.labelStyle}>{t("Announce Post")}</label>}
                                            label={t("Announce Post")}
                                        >
                                            <RadioGroupContext.Provider
                                                value={{
                                                    value: formBody.pinLocation,
                                                    onChange: (pinLocation: ICreatePostForm["pinLocation"]) =>
                                                        updateFormBody({ pinLocation }),
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
                            <div className={classes.footer}>
                                <Button onClick={() => manualDraftSave()} buttonType={ButtonTypes.OUTLINE}>
                                    {t("Save Draft")}
                                </Button>
                                <Button submit buttonType={ButtonTypes.PRIMARY}>
                                    {postMutation.isLoading ? <ButtonLoader /> : t("Post")}
                                </Button>
                            </div>
                            {draftLastSaved && (
                                <span className={classes.draftLastSaved}>
                                    {t("Draft last saved: ")}
                                    <DateTime mode={"relative"} timestamp={draftLastSaved} />
                                </span>
                            )}
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
