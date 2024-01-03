/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IComment, ICommentEdit } from "@dashboard/@types/api/comment";
import { css } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { MyEditor, MyValue } from "@library/vanilla-editor/typescript";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { FormatConversionNotice } from "@rich-editor/editor/FormatConversionNotice";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { focusEditor } from "@udecode/plate-common";
import { CommentsApi } from "@vanilla/addon-vanilla/thread/CommentsApi";
import { t } from "@vanilla/i18n";
import React, { useLayoutEffect, useRef, useState } from "react";

interface IProps {
    comment: IComment;
    commentEdit: ICommentEdit;
    onClose: () => void;
    onSuccess?: () => Promise<void>;
}

export function CommentEditor(props: IProps) {
    const { comment, commentEdit } = props;
    const [value, setValue] = useState<MyValue>();

    const patchMutation = useMutation({
        mutationFn: CommentsApi.patch,
        onSuccess: async () => {
            setValue(undefined);
            !!props.onSuccess && (await props.onSuccess?.());
        },
    });

    const editorRef = useRef<MyEditor | null>(null);
    useLayoutEffect(() => {
        if (editorRef.current) {
            focusEditor(editorRef.current);
        }
    }, []);

    const [conversionDismissed, setConversionDismissed] = useState(false);
    const needsConversion = commentEdit.format !== "rich2";

    return (
        <form
            className={css({ marginTop: 12 })}
            onSubmit={async (e) => {
                e.preventDefault();
                e.stopPropagation();
                await patchMutation.mutateAsync({
                    commentID: comment.commentID,
                    body: JSON.stringify(value),
                    format: "rich2",
                });
            }}
        >
            {needsConversion && !conversionDismissed && (
                <FormatConversionNotice
                    className={css({ marginBottom: 12 })}
                    onCancel={props.onClose}
                    onConfirm={() => setConversionDismissed(true)}
                />
            )}
            <VanillaEditor
                showConversionNotice={false}
                editorRef={editorRef}
                needsHtmlConversion={needsConversion}
                initialFormat={commentEdit.format}
                initialContent={commentEdit.body}
                onChange={(newValue) => {
                    setValue(newValue);
                }}
            />
            <div
                className={css({
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "flex-end",
                    width: "100%",
                    gap: "12px",
                    marginTop: 12,
                })}
            >
                <Button
                    onClick={() => {
                        props.onClose?.();
                    }}
                    disabled={patchMutation.isLoading}
                    buttonType={ButtonTypes.STANDARD}
                >
                    {t("Cancel")}
                </Button>
                <Button disabled={patchMutation.isLoading} submit buttonType={ButtonTypes.PRIMARY}>
                    {patchMutation.isLoading ? <ButtonLoader /> : t("Save")}
                </Button>
            </div>
        </form>
    );
}
