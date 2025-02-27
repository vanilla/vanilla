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
import { RadioPicker } from "@library/forms/RadioPicker";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { ErrorIcon } from "@library/icons/common";
import { IPickerOption, JsonSchemaForm } from "@library/json-schema-forms";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Message from "@library/messages/Message";
import { safelySerializeJSON } from "@library/utility/appUtils";
import { MyValue } from "@library/vanilla-editor/typescript";
import { EMPTY_RICH2_BODY } from "@library/vanilla-editor/utils/emptyRich2";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { useDraftContext } from "@vanilla/addon-vanilla/drafts/DraftContext";
import { makePostDraft, makePostFormValues } from "@vanilla/addon-vanilla/drafts/utils";
import { FilteredCategorySelector } from "@vanilla/addon-vanilla/createPost/FilteredCategorySelector";
import { createPostFormAssetClasses } from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset.classes";
import { ICreatePostForm, useCreatePostMutation } from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset.hooks";
import { buildSchemaFromPostFields, getPostEndpointForPostType } from "@vanilla/addon-vanilla/createPost/utils";
import { t } from "@vanilla/i18n";
import { TextBox } from "@vanilla/ui";
import { RecordID } from "@vanilla/utils";
import { useEffect, useMemo, useState } from "react";
import isEqual from "lodash-es/isEqual";
import { CreatePostFormAssetSkeleton } from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset.skeleton";
import DateTime from "@library/content/DateTime";

interface IProps {
    category: ICategory;
    postType?: PostType;
    isPreview?: boolean;
    title?: string;
    description?: string;
    subtitle?: string;
    containerOptions?: IHomeWidgetContainerOptions;
    /** Skip waiting around in tests */
    forceFormLoaded?: boolean;
}

const BASE_PERMISSIONS_OPTIONS: IPermissionOptions = {
    resourceType: "category",
    mode: PermissionMode.RESOURCE_IF_JUNCTION,
};

export interface DeserializedPostBody extends Omit<ICreatePostForm, "body"> {
    body: MyValue;
}

const INITIAL_FORM_BODY: Partial<DeserializedPostBody> = {
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
    const classes = createPostFormAssetClasses();
    const { category, postType } = props;

    const { draftID, draft, draftLoaded, updateDraft, enable, disable, draftLastSaved } = useDraftContext();
    const { hasPermission } = usePermissionsContext();

    const [tagsToAssign, setTagsToAssign] = useState<number[]>();
    const [tagsToCreate, setTagsToCreate] = useState<string[]>();
    const [formBody, setFormBody] = useState<Partial<DeserializedPostBody>>({
        ...INITIAL_FORM_BODY,
        ...(category.categoryID !== -1 && { categoryID: category.categoryID }),
        ...(postType?.postTypeID && { postTypeID: postType.postTypeID }),
    });

    const postMutation = useCreatePostMutation(tagsToAssign, tagsToCreate);

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

    const categoryID = useMemo(() => {
        return formBody.categoryID ?? category.categoryID;
    }, [category, formBody.categoryID]);

    /**
     * These specific permission sets are used when showing the announce context menu
     * option(canModerate & canAddToCollection).
     * and these when allowing announcing in recent discussion (canAnnounce & canModerate).
     */
    const canAnnounce = categoryID
        ? hasPermission("discussions.announce", { ...BASE_PERMISSIONS_OPTIONS, resourceID: +categoryID })
        : null;
    const canModerate = categoryID
        ? hasPermission("discussions.moderate", { ...BASE_PERMISSIONS_OPTIONS, resourceID: +categoryID })
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
                                        source={"Announce in  <0/> and recent discussions"}
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
        // Only after drafts are loaded we can update the draft
        if (draftLoaded) {
            // Turn the current draft into form values
            const draftAsFormValues = (draft && makePostFormValues(draft)) ?? {};
            // We compare the updated field with the current draft, if they do not match, we update the draft
            if (Object.keys(updatedField).some((key) => !isEqual(updatedField[key], draftAsFormValues?.[key]))) {
                disable();
                const draftPayload = makePostDraft({
                    ...formBody,
                    ...updatedField,
                });
                updateDraft(draftPayload);
                enable();
            }
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
        if (draftID) {
            window.history.replaceState(null, "", `/post/editdiscussion/0/${draftID}`);
        }
    }, [draftID]);

    const handleSubmit = async () => {
        const endpoint = getPostEndpointForPostType(selectedPostType ?? null);
        const body = {
            ...formBody,
            body: safelySerializeJSON(formBody.body) ?? "",
            ...(formBody.pinLocation === "none" && { pinLocation: undefined }),
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

    useEffect(() => {
        if (draft) {
            const formValuesFromDraft = makePostFormValues(draft);
            setFormBody((prev) => ({
                ...prev,
                ...formValuesFromDraft,
            }));
        }
    }, [draftLoaded]);

    const formLoaded = allPostTypes.isSuccess && draftLoaded && (formBody?.categoryID ? categoryQuery.isSuccess : true);
    const [formHasLoaded, setFromHasLoaded] = useState(props.forceFormLoaded ?? false);
    useEffect(() => {
        if (formLoaded) {
            setFromHasLoaded(true);
        }
    }, [formLoaded]);

    const manualDraftSave = () => {
        disable();
        const draftPayload = makePostDraft(formBody);
        updateDraft(draftPayload);
        enable();
    };

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
                                <div>
                                    <InputBlock
                                        legend={<label className={classes.labelStyle}>{t("Category")}</label>}
                                        required
                                    >
                                        <FilteredCategorySelector
                                            postTypeID={formBody.postTypeID}
                                            initialValues={category.categoryID !== -1 ? category.categoryID : undefined}
                                            value={formBody.categoryID}
                                            onChange={(categoryID: RecordID | undefined) => {
                                                updateFormBody({ categoryID });
                                            }}
                                            isClearable
                                            required
                                        />
                                    </InputBlock>
                                </div>
                                <div>
                                    <InputBlock
                                        legend={<label className={classes.labelStyle}>{t("Post Type")}</label>}
                                        required
                                    >
                                        <NestedSelect
                                            value={formBody.postTypeID}
                                            options={postTypeOptions ?? []}
                                            onChange={(postTypeID: string | undefined) => {
                                                updateFormBody({ postTypeID });
                                            }}
                                            isClearable
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
                                            instance={formBody.postFields}
                                            onChange={(valueDispatch) => {
                                                updateFormBody({
                                                    postFields: { ...formBody.postFields, ...valueDispatch() },
                                                });
                                            }}
                                            fieldErrors={postMutation.error?.errors}
                                        />
                                    )}
                                </div>
                                <div className={classes.postBodyContainer}>
                                    <VanillaEditor
                                        initialContent={formBody.body}
                                        onChange={(body) => updateFormBody({ body })}
                                    />
                                </div>
                                <div className={classes.tagsContainer}>
                                    <InputBlock
                                        legend={<label className={classes.labelStyle}>{t("Tags")}</label>}
                                        label={t("Tag")}
                                    >
                                        <></>
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
                            </div>
                            <div className={classes.footer}>
                                {announcementOptions && formBody?.categoryID !== -1 && (
                                    <RadioPicker
                                        className={classes.announcementPicker}
                                        value={formBody.pinLocation}
                                        onChange={(pinLocation: ICreatePostForm["pinLocation"]) =>
                                            updateFormBody({ pinLocation })
                                        }
                                        options={announcementOptions}
                                        pickerTitle={t("Announce Post")}
                                    />
                                )}

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
