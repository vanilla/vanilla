/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { IApiError } from "@library/@types/api/core";
import { tagDiscussionFormClasses } from "@library/features/discussions/forms/TagDiscussionForm.styles";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { useMutation } from "@tanstack/react-query";
import { CommunityMemberInput } from "@vanilla/addon-vanilla/forms/CommunityMemberInput";
import { DiscussionsApi } from "@vanilla/addon-vanilla/posts/DiscussionsApi";
import { t } from "@vanilla/i18n";
import { RecordID } from "@vanilla/utils";
import React, { useState } from "react";

export interface ChangeAuthorFormProps {
    discussion: IDiscussion;
    onSuccess?: () => Promise<void>;
    onError?: (error: IApiError) => void;
    onCancel: () => void;
}

/**
 * Displays the change author form
 * @deprecated Do not import this component, import ChangeAuthor instead
 */
export default function ChangeAuthorForm(props: ChangeAuthorFormProps) {
    const { onSuccess, onError, onCancel } = props;
    const { discussionID, insertUser } = props.discussion;

    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();
    const tagClasses = tagDiscussionFormClasses();

    const { addToast } = useToast();

    const [author, setAuthor] = useState<IComboBoxOption[]>([
        {
            value: insertUser?.userID ?? -3,
            label: insertUser?.name ?? "",
            data: insertUser,
        },
    ]);

    const changeAuthorMutation = useMutation({
        mutationFn: async (authorID: RecordID) =>
            await DiscussionsApi.patch(discussionID, {
                insertUserID: authorID,
            }),
        onSuccess: async () => {
            addToast({
                body: t("Success! Author has been changed"),
                autoDismiss: true,
            });
            !!onSuccess && (await onSuccess());
        },
        onError: (error: IApiError) => {
            addToast({
                body: (
                    <>
                        {t("There was an error changing the author")} {error.message}
                    </>
                ),
                autoDismiss: false,
            });
            onError && onError(error);
        },
    });

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                changeAuthorMutation.mutate(author[0].value);
            }}
        >
            <Frame
                header={<FrameHeader closeFrame={() => onCancel()} title={t("Change Author")} />}
                bodyWrapClass={tagClasses.modalSuggestionOverride}
                body={
                    <FrameBody>
                        <div className={classesFrameBody.contents}>
                            <CommunityMemberInput
                                onChange={setAuthor}
                                value={author}
                                label={t("Author")}
                                isClearable={false}
                                maxHeight={100}
                            />
                        </div>
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button
                            buttonType={ButtonTypes.TEXT}
                            onClick={() => onCancel()}
                            className={classFrameFooter.actionButton}
                        >
                            {t("Cancel")}
                        </Button>
                        <Button
                            submit
                            disabled={changeAuthorMutation.isLoading}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                            className={classFrameFooter.actionButton}
                        >
                            {changeAuthorMutation.isLoading ? <ButtonLoader /> : t("Save")}
                        </Button>
                    </FrameFooter>
                }
            />
        </form>
    );
}
