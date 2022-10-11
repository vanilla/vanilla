/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import Button from "@library/forms/Button";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import { cx } from "@emotion/css";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@library/utility/appUtils";
import { TagsInput } from "@library/features/tags/TagsInput";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { usePutDiscussionTags } from "@library/features/discussions/discussionHooks";
import { tagDiscussionFormClasses } from "@library/features/discussions/forms/TagDiscussionForm.loadable.styles";
import { useFormik } from "formik";

type FormValues = {
    tagIDs: IComboBoxOption[];
};
export interface IProps {
    onCancel: () => void;
    onSuccess: () => void;
    discussion: IDiscussion;
}

/**
 * Displays the discussion tagging modal
 * @deprecated Do not import this component, import TagDiscussionForm instead
 */
export default function TagDiscussionFormLoadable(props: IProps) {
    const { onCancel, onSuccess, discussion } = props;

    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();
    const classes = tagDiscussionFormClasses();
    const putDiscussionTags = usePutDiscussionTags(discussion.discussionID);

    const { handleSubmit, setFieldValue, values, isSubmitting, errors } = useFormik<FormValues>({
        initialValues: {
            tagIDs:
                discussion.tags?.map(
                    ({ tagID, urlcode, name }): IComboBoxOption => ({
                        value: tagID,
                        label: name,
                        data: urlcode,
                    }),
                ) ?? [],
        },
        onSubmit: async function ({ tagIDs }, { setErrors }) {
            if (tagIDs) {
                try {
                    await putDiscussionTags(tagIDs.map(({ value }) => value as number));
                    onSuccess();
                } catch (error) {
                    setErrors({ tagIDs: error.message });
                }
            }
        },
    });

    return (
        <form onSubmit={handleSubmit}>
            <Frame
                header={<FrameHeader closeFrame={onCancel} title={t("Tag")} />}
                bodyWrapClass={classes.modalSuggestionOverride}
                body={
                    <FrameBody>
                        <div className={cx("frameBody-contents", classesFrameBody.contents)}>
                            <TagsInput
                                id="tagIDs"
                                type="User"
                                label={null}
                                value={values.tagIDs}
                                onChange={(options: IComboBoxOption[]) => {
                                    setFieldValue("tagIDs", options);
                                }}
                            />
                            {errors.tagIDs && <div className={classes.error}>{errors.tagIDs}</div>}
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
                            disabled={isSubmitting}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                            className={classFrameFooter.actionButton}
                            submit
                        >
                            {isSubmitting ? <ButtonLoader /> : t("Save")}
                        </Button>
                    </FrameFooter>
                }
            />
        </form>
    );
}
