/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useEffect, useMemo, useState } from "react";
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
import { useBulkDiscussionMove } from "@library/features/discussions/discussionHooks";
import { bulkDiscussionsClasses } from "@library/features/discussions/forms/BulkDiscussions.classes";
import Checkbox from "@library/forms/Checkbox";
import { useToast } from "@library/features/toaster/ToastContext";
import Translate from "@library/content/Translate";

type FormValues = {
    category?: {
        label: ICategoryFragment["name"];
        value: ICategoryFragment["categoryID"];
    };
    addRedirects: boolean;
};

export interface MoveDiscussionFormProps {
    onCancel: () => void;
    onSuccess?: () => Promise<void>;
    isLoading?: boolean;
    discussion: IDiscussion;
}

export default function MoveDiscussionForm({ onCancel, discussion, onSuccess }: MoveDiscussionFormProps) {
    const [moveIsTriggered, setMoveIsTriggered] = useState<boolean>(false);

    const toast = useToast();

    const { values, setFieldValue, handleSubmit, dirty, isSubmitting, resetForm } = useFormik<FormValues>({
        initialValues: {
            category: discussion.category
                ? {
                      label: discussion.category.name,
                      value: discussion.category.categoryID,
                  }
                : undefined,
            addRedirects: false,
        },
        onSubmit: () => {
            try {
                moveSelectedDiscussions();
                setMoveIsTriggered(true);
            } catch (error) {
                toast.addToast({
                    autoDismiss: false,
                    dismissible: true,
                    body: <Translate source="There was a problem moving <0/>" c0={discussion.name} />,
                });
                onCancel();
                setMoveIsTriggered(false);
            }
        },
    });

    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();
    const classes = discussionListClasses();

    const { isSuccess, failedDiscussions, moveSelectedDiscussions } = useBulkDiscussionMove(
        [discussion.discussionID],
        values.category?.value,
        values.addRedirects,
        true,
    );

    useEffect(() => {
        if (moveIsTriggered) {
            if (isSuccess) {
                !!onSuccess && onSuccess();
                toast.addToast({
                    autoDismiss: true,
                    body: <>{t("Selected discussion has been moved successfully.")}</>,
                });
                resetForm();
            } else if (failedDiscussions) {
                toast.addToast({
                    autoDismiss: false,
                    dismissible: true,
                    body: <>{errorMessage}</>,
                });
                onCancel();
            }
            setMoveIsTriggered(false);
        }
    }, [isSuccess, failedDiscussions]);

    const errorMessage = useMemo<string | null>(() => {
        return failedDiscussions
            ? `${t("There was a problem moving")} ${Object.values(failedDiscussions)
                  .map(({ name }) => `"${name}"`)
                  .join(", ")}`
            : null;
    }, [failedDiscussions]);

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
                                    maxHeight={100}
                                />
                            </div>
                            <div className={bulkDiscussionsClasses().separatedSection}>
                                <Checkbox
                                    className={bulkDiscussionsClasses().checkboxLabel}
                                    label={t("Leave a redirect link")}
                                    checked={values.addRedirects}
                                    onChange={(e) => setFieldValue("addRedirects", e.target.checked)}
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
