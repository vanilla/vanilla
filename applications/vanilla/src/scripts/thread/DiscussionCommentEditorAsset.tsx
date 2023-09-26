/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { PageBox } from "@library/layout/PageBox";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { MyValue } from "@library/vanilla-editor/typescript";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { CommentsApi } from "@vanilla/addon-vanilla/thread/CommentsApi";
import { t } from "@vanilla/i18n";
import { RecordID } from "@vanilla/utils";
import React, { useState } from "react";

interface IProps {
    discussionID: RecordID;
    categoryID: number;
}

export function DiscussionCommentEditorAsset(props: IProps) {
    const [value, setValue] = useState<MyValue | undefined>();
    const [editorKey, setEditorKey] = useState(0);
    const queryClient = useQueryClient();
    const postMutation = useMutation({
        mutationFn: CommentsApi.post,
        onSuccess: () => {
            setValue(undefined);
            setEditorKey((existing) => existing + 1);
            queryClient.invalidateQueries({ queryKey: ["commentsList"] });
        },
    });

    return (
        <PageBox className={css({ marginTop: 24 })}>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    postMutation.mutate({
                        format: "rich2",
                        body: JSON.stringify(value),
                        discussionID: props.discussionID,
                    });
                }}
            >
                <PageHeadingBox title={t("Leave a Comment")} />
                <VanillaEditor
                    key={editorKey}
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
                    <Button disabled={postMutation.isLoading} buttonType={ButtonTypes.STANDARD}>
                        {t("Save Draft")}
                    </Button>
                    <Button disabled={postMutation.isLoading} submit buttonType={ButtonTypes.PRIMARY}>
                        {postMutation.isLoading ? <ButtonLoader /> : t("Post Comment")}
                    </Button>
                </div>
            </form>
        </PageBox>
    );
}

export default DiscussionCommentEditorAsset;
