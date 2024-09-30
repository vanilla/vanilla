/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDraft } from "@dashboard/@types/api/draft";
import { useDebouncedInput } from "@dashboard/hooks";
import { useToast } from "@library/features/toaster/ToastContext";
import { MyValue } from "@library/vanilla-editor/typescript";
import { isMyValue } from "@library/vanilla-editor/utils/isMyValue";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import CommentsApi from "@vanilla/addon-vanilla/thread/CommentsApi";
import { DraftsApi } from "@vanilla/addon-vanilla/thread/DraftsApi";
import { logError, RecordID } from "@vanilla/utils";
import isEqual from "lodash-es/isEqual";
import { useEffect, useRef, useState } from "react";
import { IComment, IPremoderatedRecordResponse } from "@dashboard/@types/api/comment";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { NewCommentEditor } from "@vanilla/addon-vanilla/thread/components/NewCommentEditor";
import { t } from "@vanilla/i18n";
import { getLocalStorageOrDefault } from "@vanilla/react-utils";

interface IDraftProps {
    draftID: number;
    body: string;
    dateUpdated: string;
    format: string;
}

interface IProps {
    discussionID: RecordID;
    categoryID: number;
    draft?: IDraftProps;
    isPreview?: boolean;
}

export const EMPTY_DRAFT: MyValue = [{ type: "p", children: [{ text: "" }] }];

export function DiscussionCommentEditorAsset(props: IProps = { discussionID: "", categoryID: 0 }) {
    const { draft, discussionID } = props;
    const [ownDraft, setDraft] = useState<IDraftProps | undefined>();
    const { addToast } = useToast();
    const [value, setValue] = useState<MyValue | undefined>();
    const [editorKey, setEditorKey] = useState(0);
    const queryClient = useQueryClient();
    const lastSaved = useRef<Date | null>(draft ? new Date(draft.dateUpdated) : null);

    const cacheDraftParentID = getLocalStorageOrDefault(`commentDraftParentID-${discussionID}`, null);

    const resetState = () => {
        setDraft(undefined);
        lastSaved.current = null;
        setValue(EMPTY_DRAFT);
        setEditorKey((existing) => existing + 1);
    };

    // FIXME: Need to integrate with the new draft system
    useEffect(() => {
        if (cacheDraftParentID === discussionID) {
            setDraft(draft);
        }
    }, [draft, cacheDraftParentID]);

    const postMutation = useMutation({
        mutationFn: async (body: string) => {
            const response = await CommentsApi.post({
                format: "rich2",
                discussionID,
                ...(ownDraft?.draftID && { draftID: ownDraft?.draftID }),
                body,
            });
            if ("status" in response && response.status === 202) {
                addToast({
                    body: t("Your comment will appear after it is approved."),
                    dismissible: true,
                    autoDismiss: false,
                });
            }
            return response;
        },
    });

    async function handlePostCommentSuccess(comment: IComment | IPremoderatedRecordResponse) {
        resetState();
        await queryClient.invalidateQueries({ queryKey: ["discussion"] });
        await queryClient.invalidateQueries({ queryKey: ["commentList"] });
        await queryClient.invalidateQueries({ queryKey: ["commentThread"] });
        //FIXME: comment permalinks don't work in new thread view yet
        // window.location.href = comment.url;
    }

    const draftMutation = useMutation({
        mutationFn: async () => {
            const payload: DraftsApi.PostParams = {
                attributes: {
                    format: "rich2",
                    body: JSON.stringify(value),
                },
                parentRecordID: props.discussionID,
                recordType: "comment",
            };

            if (ownDraft?.draftID) {
                return DraftsApi.patch({
                    ...payload,
                    draftID: ownDraft?.draftID,
                });
            } else {
                return DraftsApi.post(payload);
            }
        },
        onSuccess(data: IDraft) {
            lastSaved.current = new Date();
            setDraft({
                draftID: data.draftID,
                body: data.attributes.body,
                dateUpdated: data.dateUpdated,
                format: data.attributes.format,
            });
        },
        onError(error: IError) {
            addToast({
                body: error,
                autoDismiss: false,
            });
        },
        mutationKey: ["draft"],
    });

    const debouncedComment = useDebouncedInput(value, 500);

    useEffect(() => {
        //Ensure new value is not an empty draft
        // Perhaps if it is, it should delete the current draft?
        if (value && isMyValue(value) && !isEqual(value, EMPTY_DRAFT)) {
            // Ensure the current value is different from the last saved draft;
            let parsedBody: string | MyValue | undefined = ownDraft?.body;
            try {
                parsedBody = ownDraft?.format === "rich2" ? JSON.parse(ownDraft?.body ?? "{}") : ownDraft?.body;
            } catch (error) {
                logError(error);
            }
            if (!isEqual(value, parsedBody)) {
                draftMutation.mutateAsync();
            }
        }
    }, [debouncedComment]);

    return (
        <NewCommentEditor
            editorKey={editorKey}
            value={value}
            onValueChange={setValue}
            onPublish={async (value) => {
                const newComment = await postMutation.mutateAsync(JSON.stringify(value));
                await handlePostCommentSuccess(newComment);
            }}
            publishLoading={postMutation.isLoading}
            draft={ownDraft}
            onDraft={() => {
                draftMutation.mutate();
            }}
            draftLoading={draftMutation.isLoading}
            draftLastSaved={lastSaved.current}
            isPreview={props.isPreview}
        />
    );
}

export default DiscussionCommentEditorAsset;
