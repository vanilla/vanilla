/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { roleLookUp } from "@dashboard/moderation/communityManagmentUtils";
import { PostFieldList } from "@dashboard/postTypes/components/PostFieldList";
import { postTypeSettingsClasses } from "@dashboard/postTypes/pages/postTypeSettings.classes";
import { PostType } from "@dashboard/postTypes/postType.types";
import { PostTypeEditProvider, usePostTypeEdit } from "@dashboard/postTypes/PostTypeEditContext";
import { originalPostTypeAsOptions } from "@dashboard/postTypes/utils";
import { css } from "@emotion/css";
import { ErrorPageBoundary } from "@library/errorPages/ErrorPageBoundary";
import { AutoWidthInput } from "@library/forms/AutoWidthInput";
import { autoWidthInputClasses } from "@library/forms/AutoWidthInput.classes";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { EditIcon } from "@library/icons/common";
import { SchemaFormBuilder } from "@library/json-schema-forms";
import Heading from "@library/layout/Heading";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Loader from "@library/loaders/Loader";
import ModalConfirm from "@library/modal/ModalConfirm";
import BackLink from "@library/routing/links/BackLink";
import SmartLink from "@library/routing/links/SmartLink";
import { formatUrl, t } from "@library/utility/appUtils";
import { slugify } from "@vanilla/utils";

import { useRef, useState } from "react";
import { RouteComponentProps, useHistory } from "react-router";

interface IProps
    extends RouteComponentProps<{
        postTypeID: PostType["postTypeID"];
    }> {}

function PostTypeEditImpl(props: IProps) {
    const { mode, isDirty, isLoading, dirtyPostType, updatePostType, savePostType, isSaving, error } =
        usePostTypeEdit();
    const history = useHistory();

    const classes = postTypeSettingsClasses();

    const editableRef = useRef<HTMLInputElement | null>(null);
    const focusAndSelectAll = (event?: any) => {
        if (editableRef.current && editableRef.current !== document.activeElement) {
            if (event) event.preventDefault();
            editableRef.current.focus();
            editableRef.current.select();
        }
    };

    const [shouldCreateApiLabel, setShouldCreateApiLabel] = useState(mode === "new");
    const [showConfirmExit, setShowConfirmExit] = useState(false);

    const schema = new SchemaFormBuilder()
        .dropdown(
            "parentPostTypeID",
            "Post Base Type",
            "The base type your custom post type will be associated with.",
            originalPostTypeAsOptions(),
            mode === "edit",
        )
        .required()
        .textBox(
            "postTypeID",
            "API Label",
            "The unique identifier for your custom post type. Once selected, this cannot be changed.",
            mode === "edit",
            "[a-zA-Z0-9-_]",
        )
        .required()
        .textBox(
            "postButtonLabel",
            "Post Button Label",
            "The name which will appear on the button to create a new post of this type.",
        )
        .textBox("postHelperText", "Post Helper Text", "The name which will appear on the post itself.")
        .selectLookup(
            "roleIDs",
            "Creation Permission",
            "The roles that can create posts of this type.",
            roleLookUp,
            true,
        )
        .getSchema();

    const handleSubmit = async () => {
        await savePostType();
    };

    return (
        <>
            <DashboardHeaderBlock
                title={null}
                actionButtons={
                    <>
                        <div className={classes.titleLayout}>
                            <div>
                                <BackLink
                                    visibleLabel={true}
                                    onClick={() => {
                                        isDirty ? setShowConfirmExit(true) : history.push("/settings/post-types");
                                    }}
                                    className={classes.backLink}
                                />
                            </div>

                            <div className={classes.postNameLayout}>
                                <AutoWidthInput
                                    disabled={isLoading}
                                    onChange={(event) => {
                                        updatePostType({
                                            ...dirtyPostType,
                                            name: event.target.value,
                                            ...(shouldCreateApiLabel && { postTypeID: slugify(event.target.value) }),
                                        });
                                    }}
                                    className={autoWidthInputClasses().themeInput}
                                    ref={(ref) => (editableRef.current = ref)}
                                    value={dirtyPostType?.name}
                                    placeholder={t("Post Type must have a name")}
                                    maxLength={300}
                                    onKeyDown={(event) => {
                                        if (event.key === "Enter") {
                                            event.preventDefault();
                                            (event.target as HTMLElement).blur();
                                        }
                                    }}
                                    onMouseDown={focusAndSelectAll}
                                />
                                <Button buttonType={ButtonTypes.ICON} onClick={focusAndSelectAll}>
                                    <EditIcon small />
                                </Button>
                            </div>

                            <div>
                                <Button
                                    buttonType={ButtonTypes.OUTLINE}
                                    onClick={() => handleSubmit()}
                                    disabled={isSaving || isLoading}
                                >
                                    {isSaving ? <ButtonLoader /> : t("Save")}
                                </Button>
                            </div>
                        </div>
                    </>
                }
            />
            <ErrorPageBoundary>
                <section>
                    {isLoading ? (
                        <Loader />
                    ) : (
                        <>
                            <DashboardHeaderBlock title={t("Properties")} />
                            <DashboardSchemaForm
                                fieldErrors={error?.response?.data?.errors}
                                schema={schema}
                                instance={
                                    mode === "new" && dirtyPostType?.postTypeID == "-1"
                                        ? { ...dirtyPostType, postTypeID: undefined }
                                        : dirtyPostType
                                }
                                onChange={(values) => {
                                    if (values()["postTypeID"]) {
                                        setShouldCreateApiLabel(false);
                                    }
                                    updatePostType({ ...values() });
                                }}
                            />
                            <PostFieldList />
                        </>
                    )}
                </section>
            </ErrorPageBoundary>
            <DashboardHelpAsset>
                <Heading>{t("About Post Types and Post Fields")}</Heading>
                <p>{t("You can configure and manage the post types available on your community from this page.")} </p>
                <SmartLink to="https://success.vanillaforums.com/kb/articles/TODO_MAKE_A_DRAFT">
                    {t("Learn more about post types and post fields")}
                </SmartLink>
            </DashboardHelpAsset>
            <ModalConfirm
                isVisible={showConfirmExit}
                title={t("Unsaved Changes")}
                onCancel={() => setShowConfirmExit(false)}
                onConfirm={() => (window.location.href = formatUrl("/settings/post-types"))}
                confirmTitle={t("Discard")}
                bodyClassName={css({ justifyContent: "start" })}
            >
                {t("You have unsaved changes. Are you sure you want to exit without saving?")}
            </ModalConfirm>
        </>
    );
}

export function PostTypeEdit(props: IProps) {
    const postTypeID = props.match.params.postTypeID;
    const copyRef =
        props.location.search.includes("copy-post-type-id") && props.location.search.split("?copy-post-type-id=")[1];
    const mode = postTypeID && !copyRef ? "edit" : copyRef && !postTypeID ? "copy" : "new";

    return (
        <PostTypeEditProvider postTypeID={postTypeID} mode={mode}>
            <PostTypeEditImpl {...props} />
        </PostTypeEditProvider>
    );
}

export default PostTypeEdit;
