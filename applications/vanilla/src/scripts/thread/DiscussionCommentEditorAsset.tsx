/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDraft } from "@dashboard/@types/api/draft";
import { useDebouncedInput } from "@dashboard/hooks";
import { css, cx } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { PageBox } from "@library/layout/PageBox";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { metasClasses } from "@library/metas/Metas.styles";
import { MyValue } from "@library/vanilla-editor/typescript";
import { isMyValue } from "@library/vanilla-editor/utils/isMyValue";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { CommentsApi } from "@vanilla/addon-vanilla/thread/CommentsApi";
import { discussionCommentEditorClasses } from "@vanilla/addon-vanilla/thread/DiscussionCommentEditorAsset.classes";
import { DraftsApi } from "@vanilla/addon-vanilla/thread/DraftsApi";
import { t } from "@vanilla/i18n";
import { logError, RecordID } from "@vanilla/utils";
import isEqual from "lodash/isEqual";
import React, { useEffect, useRef, useState } from "react";

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
}

const EMPTY_DRAFT: MyValue = [{ type: "p", children: [{ text: "" }] }];

export function DiscussionCommentEditorAsset(props: IProps) {
    const { draft, discussionID } = props;
    const [ownDraft, setDraft] = useState<IDraftProps | undefined>(draft);
    const { addToast } = useToast();
    const [value, setValue] = useState<MyValue | undefined>();
    const [editorKey, setEditorKey] = useState(0);
    const queryClient = useQueryClient();
    const lastSaved = useRef<Date | null>(draft ? new Date(draft.dateUpdated) : null);

    const resetState = () => {
        setDraft(undefined);
        lastSaved.current = null;
        setValue(EMPTY_DRAFT);
        setEditorKey((existing) => existing + 1);
    };

    const postMutation = useMutation({
        mutationFn: async (body: string) =>
            await CommentsApi.post({
                format: "rich2",
                discussionID,
                ...(ownDraft?.draftID && { draftID: ownDraft?.draftID }),
                body,
            }),
        onSuccess: () => {
            resetState();
            queryClient.invalidateQueries({ queryKey: ["commentList"] });
        },
    });

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
        onError(error) {
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

    const classes = discussionCommentEditorClasses();

    return (
        <PageBox
            options={{
                borderType: BorderType.NONE,
            }}
            className={classes.pageBox}
        >
            <form
                onSubmit={async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    await postMutation.mutateAsync(JSON.stringify(value));
                }}
            >
                <PageHeadingBox title={t("Leave a Comment")} />
                <VanillaEditor
                    key={editorKey}
                    initialFormat={ownDraft?.format}
                    initialContent={ownDraft?.body}
                    onChange={(newValue) => {
                        setValue(newValue);
                    }}
                />
                <div className={classes.editorPostActions}>
                    {lastSaved.current && (
                        <span className={cx(metasClasses().metaStyle, classes.draftMessage)}>
                            {draftMutation.isLoading ? (
                                t("Saving draft...")
                            ) : (
                                <Translate
                                    source="Draft saved <0/>"
                                    c0={<DateTime timestamp={lastSaved.current.toUTCString()} mode="relative" />}
                                />
                            )}
                        </span>
                    )}
                    <Button
                        disabled={postMutation.isLoading || draftMutation.isLoading || isEqual(value, EMPTY_DRAFT)}
                        buttonType={ButtonTypes.STANDARD}
                        onClick={() => draftMutation.mutate()}
                    >
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
