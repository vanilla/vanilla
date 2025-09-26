/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import Translate from "@library/content/Translate";
import { IBulkActionForm } from "@library/bulkActions/BulkActions.types";
import { IFieldError, JsonSchema, JsonSchemaForm } from "@library/json-schema-forms";
import { FormControlGroup, FormControlWithNewDropdown } from "@library/forms/FormControl";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { t } from "@library/utility/appUtils";
import { useState } from "react";
import { useCommentsBulkActionsContext } from "@vanilla/addon-vanilla/comments/bulkActions/CommentsBulkActionsContext";
import { commentsBulkActions } from "@vanilla/addon-vanilla/comments/bulkActions/CommentsBulkActions.classes";
import { useMutation } from "@tanstack/react-query";
import apiv2 from "@library/apiv2";
import { useToast } from "@library/features/toaster/ToastContext";
import SmartLink from "@library/routing/links/SmartLink";
import { IServerError } from "@library/@types/api/core";
import { IError } from "@library/errorPages/CoreErrorMessages";
import Message from "@library/messages/Message";
import { ErrorIcon } from "@library/icons/common";
import ErrorMessages from "@library/forms/ErrorMessages";

interface ISplitBulkActionFormValues {
    newPost?: {
        name: string;
        categoryID: number;
        postType: string;
        authorType: string;
    };
}

export default function CommentsSplitBulkActionLoadable(props: IBulkActionForm) {
    const { onCancel } = props;
    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();
    const classes = commentsBulkActions();
    const { optionalPostTypes, checkedCommentIDs, removeCheckedCommentsByIDs, handleMutateSuccess } =
        useCommentsBulkActionsContext();
    const { addToast } = useToast();

    const [topLevelErrors, setTopLevelErrors] = useState<IError[]>([]);
    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]>>({});
    const [values, setValues] = useState<ISplitBulkActionFormValues>({});

    const schema: JsonSchema = {
        type: "object",
        properties: {
            newPost: {
                type: "object",
                properties: {
                    name: {
                        type: "string",
                        "x-control": {
                            inputType: "textBox",
                            label: t("New Post"),
                        },
                    },
                    categoryID: {
                        type: "integer",
                        "x-control": {
                            label: t("Category"),
                            inputType: "dropDown",
                            choices: {
                                api: {
                                    searchUrl: `categories/search?displayAs[]=Discussions&query=%s`,
                                    singleUrl: `/categories/%s`,
                                    labelKey: "name",
                                    valueKey: "categoryID",
                                },
                            },
                        },
                    },
                    // this one should be changed to API when we launch custom post types,
                    // which might bring additional changes to the whole component to support required fields for custom post types
                    postType: {
                        "x-control": {
                            label: "Post Type",
                            inputType: "radioPicker",
                            options: [{ value: "discussion", label: "Discussion" }, ...optionalPostTypes],
                        },
                    },
                    authorType: {
                        "x-control": {
                            label: "Post Author",
                            inputType: "radioPicker",
                            options: [
                                { value: "me", label: "Me" },
                                { value: "system", label: "System" },
                            ],
                        },
                    },
                },
                required: ["name", "categoryID", "postType", "authorType"],
            },
        },
    };

    const splitComments = useMutation({
        mutationFn: async () => {
            const payload = {
                ...values,
                commentIDs: checkedCommentIDs,
            };
            const response = await apiv2.post("/discussions/split", {
                ...payload,
            });
            return response.data;
        },
        mutationKey: ["split_comments", checkedCommentIDs],
        onSuccess(response) {
            addToast({
                dismissible: true,
                body: (
                    <>
                        {`${t("Post has been split.")} `}
                        <SmartLink to={response.newPostUrl}>{t("View New Post")}</SmartLink>
                    </>
                ),
            });
            removeCheckedCommentsByIDs(checkedCommentIDs);
            props.onSuccess();
            void handleMutateSuccess();
        },
        onError(error: IServerError) {
            if (error.errors && Object.keys(error.errors ?? {}).length) {
                setFieldErrors(error.errors ?? []);
            } else if (error.description || error.message) {
                setTopLevelErrors([
                    {
                        message: error.description || error.message,
                    },
                ]);
            }
        },
    });

    return (
        <form
            onSubmit={async (e) => {
                e.preventDefault();
                e.stopPropagation();
                await splitComments.mutateAsync();
            }}
        >
            <Frame
                header={<FrameHeader closeFrame={onCancel} title={t("Split Comments into New Post")} />}
                body={
                    <FrameBody>
                        <div className={classesFrameBody.contents}>
                            <>
                                {topLevelErrors && topLevelErrors.length > 0 && (
                                    <Message
                                        type="error"
                                        stringContents={topLevelErrors[0].message}
                                        icon={<ErrorIcon />}
                                        contents={<ErrorMessages errors={topLevelErrors} />}
                                        className={classes.topLevelError}
                                    />
                                )}
                                <div className={classes.modalHeader}>
                                    <Translate
                                        source={
                                            "<0/> selected comments and their replies will be moved into a single New Post"
                                        }
                                        c0={checkedCommentIDs.length}
                                    />
                                </div>
                                <JsonSchemaForm
                                    schema={schema}
                                    instance={values}
                                    FormControl={FormControlWithNewDropdown}
                                    FormControlGroup={FormControlGroup}
                                    onChange={setValues}
                                    fieldErrors={fieldErrors}
                                />
                            </>
                        </div>
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button
                            buttonType={ButtonTypes.TEXT}
                            onClick={onCancel}
                            className={classFrameFooter.actionButton}
                        >
                            {t("Cancel")}
                        </Button>
                        <Button buttonType={ButtonTypes.TEXT_PRIMARY} className={classFrameFooter.actionButton} submit>
                            {t("Split")}
                        </Button>
                    </FrameFooter>
                }
            />
        </form>
    );
}
