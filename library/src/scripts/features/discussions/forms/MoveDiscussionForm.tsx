/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { t } from "@library/utility/appUtils";
import { useFormik } from "formik";
import Frame from "@library/layout/frame/Frame";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameFooter from "@library/layout/frame/FrameFooter";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import FrameBody from "@library/layout/frame/FrameBody";
import CommunityCategoryInput from "@vanilla/addon-vanilla/forms/CommunityCategoryInput";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { discussionListClasses } from "@library/features/discussions/DiscussionList.classes";
import { CategoryDisplayAs, ICategoryFragment } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { useDiscussionPatch } from "@library/features/discussions/discussionHooks";

type FormValues = {
    category?: {
        label: ICategoryFragment["name"];
        value: ICategoryFragment["categoryID"];
    };
};

interface IProps {
    onCancel: () => void;
    onSuccess: () => void;
    isLoading?: boolean;
    discussion: IDiscussion;
}

export default function MoveDiscussionForm({ onCancel, discussion, onSuccess }: IProps) {
    const { patchDiscussion } = useDiscussionPatch(discussion.discussionID, "move");
    const formik = useFormik<FormValues>({
        initialValues: {
            category: discussion.category
                ? {
                      label: discussion.category.name,
                      value: discussion.category.categoryID,
                  }
                : undefined,
        },
        onSubmit: async ({ category }, helpers) => {
            const categoryID = category!.value;
            await patchDiscussion({ categoryID });

            onSuccess();
            helpers.resetForm();
        },
    });

    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();
    const classes = discussionListClasses();

    const { values, setFieldValue, handleSubmit, dirty, isSubmitting } = formik;

    return (
        <form onSubmit={handleSubmit}>
            <Frame
                header={<FrameHeader closeFrame={onCancel} title={t("Move Discussion")} />}
                body={
                    <FrameBody>
                        <div className={classesFrameBody.contents}>
                            <div className={classes.options.move}>
                                <CommunityCategoryInput
                                    displayAs={CategoryDisplayAs.DISCUSSIONS}
                                    placeholder={t("Select...")}
                                    label={t("Category")}
                                    onChange={(option) => {
                                        const selected = option[0];
                                        setFieldValue("category", selected);
                                    }}
                                    value={values.category ? [values.category] : []}
                                    /** FIXME: This maxHeight value prevents blur by stopping
                                     * dropdowns from overflowing with the modal
                                     * Remove with https://github.com/vanilla/vanilla-cloud/issues/3155
                                     */
                                    maxHeight={130}
                                />
                            </div>
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
                            submit
                            disabled={values.category?.value === undefined || !dirty || isSubmitting}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                            className={classFrameFooter.actionButton}
                        >
                            {isSubmitting ? <ButtonLoader /> : t("Submit")}
                        </Button>
                    </FrameFooter>
                }
            />
        </form>
    );
}
