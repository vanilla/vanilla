/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useEffect, useMemo, useState } from "react";
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
import ButtonLoader from "@library/loaders/ButtonLoader";
import { discussionListClasses } from "@library/features/discussions/DiscussionList.classes";
import { ICategoryFragment } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { useBulkDiscussionMove } from "@library/features/discussions/discussionHooks";
import { bulkDiscussionsClasses } from "@library/features/discussions/forms/BulkDiscussions.classes";
import Checkbox from "@library/forms/Checkbox";
import { useToast } from "@library/features/toaster/ToastContext";
import Translate from "@library/content/Translate";
import { CategoryDropdown } from "@library/forms/nestedSelect/presets/CategoryDropdown";

type FormValues = {
    categoryID?: ICategoryFragment["categoryID"];
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
            categoryID: discussion.category?.categoryID,
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
        values.categoryID,
        values.addRedirects,
        true,
    );

    useEffect(() => {
        if (moveIsTriggered) {
            if (isSuccess) {
                void onSuccess?.();
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
                                <CategoryDropdown
                                    onChange={(categoryID: number) => {
                                        void setFieldValue("categoryID", categoryID);
                                    }}
                                    value={values.categoryID}
                                    label={t("Category")}
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
                            disabled={values.categoryID === undefined || !dirty || isSubmitting}
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
