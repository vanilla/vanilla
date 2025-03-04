/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import Button from "@library/forms/Button";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@library/utility/appUtils";
import { tagDiscussionFormClasses } from "@library/features/discussions/forms/TagDiscussionForm.loadable.styles";
import { useState } from "react";
import { ITag } from "@library/features/tags/TagsReducer";
import { useMutation } from "@tanstack/react-query";
import { slugify } from "@vanilla/utils";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { TagPostUI } from "@library/features/discussions/forms/TagPostUI";

export interface IProps {
    onCancel: () => void;
    onSuccess?: () => Promise<void>;
    discussion: IDiscussion;
}

/**
 * Displays the discussion tagging modal
 * @deprecated Do not import this component, import TagDiscussionForm instead
 */
export default function TagDiscussionFormLoadable(props: IProps) {
    const { onCancel, discussion } = props;

    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();
    const classes = tagDiscussionFormClasses();

    const initialValues = discussion.tags?.filter((tag) => tag.type === "User").map((tag) => tag.tagID);
    const [tagsToAssign, setTagsToAssign] = useState<number[]>();
    const [tagsToCreate, setTagsToCreate] = useState<string[]>();

    const makeTag = async (tagName: ITag["name"]): Promise<ITag> => {
        const createTagBody = {
            name: tagName,
            url: slugify(tagName),
        };
        const response = await apiv2.post("/tags", createTagBody);
        return response.data;
    };

    const tagDiscussionMutation = useMutation<ITag | ITag, IError>({
        mutationFn: async () => {
            // First create all the new tags
            let newlyCreatedTagIDs: Array<ITag["tagID"]> = [];
            if (tagsToCreate) {
                const newTags = await Promise.all(
                    tagsToCreate.map(async (tagName) => {
                        const response = await makeTag(tagName);
                        return response;
                    }),
                );
                newlyCreatedTagIDs = newTags.map((tag) => tag.tagID);
            }
            // Not merge list of created and existing tags before saving to the discussion
            const allTagIDs = [...(tagsToAssign ?? []), ...newlyCreatedTagIDs];
            const response = await apiv2.put(`/discussions/${discussion.discussionID}/tags`, {
                tagIDs: allTagIDs,
            });
            return response.data;
        },
        onSuccess: async () => {
            !!props.onSuccess && (await props.onSuccess());
        },
        onError: (error) => {
            console.error(error);
            return error.response.data;
        },
    });

    const handleSubmit = async (e) => {
        e.preventDefault();
        e.stopPropagation();
        tagDiscussionMutation.mutate();
    };

    return (
        <form onSubmit={handleSubmit}>
            <Frame
                header={<FrameHeader closeFrame={onCancel} title={t("Tag")} />}
                bodyWrapClass={classes.modalSuggestionOverride}
                body={
                    <FrameBody>
                        <div className={classesFrameBody.contents}>
                            <TagPostUI
                                initialTagIDs={initialValues}
                                onSelectedExistingTag={setTagsToAssign}
                                onSelectedNewTag={setTagsToCreate}
                                fieldErrors={tagDiscussionMutation?.error}
                                showPopularTags
                            />
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
                        <Button
                            disabled={tagDiscussionMutation.isLoading}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                            className={classFrameFooter.actionButton}
                            submit
                        >
                            {tagDiscussionMutation.isLoading ? <ButtonLoader /> : t("Save")}
                        </Button>
                    </FrameFooter>
                }
            />
        </form>
    );
}
