/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { usePatchAnswerStatus } from "@QnA/hooks/usePatchAnswerStatus";
import { IComment, QnAStatus } from "@dashboard/@types/api/comment";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { FormControl, FormControlGroup } from "@library/forms/FormControl";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { t } from "@vanilla/i18n";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { useFormik } from "formik";
import React from "react";

export interface IProps {
    onCancel: () => void;
    onSuccess?: () => Promise<void>;
    comment: IComment;
}

interface IFormValues {
    status: QnAStatus;
}

export default function ChangeQnaStatusFormLoadable(props: IProps) {
    const { onCancel, onSuccess, comment } = props;

    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();

    const { addToast } = useToast();

    const schema: JsonSchema = {
        type: "object",
        properties: {
            status: {
                type: "string",
                "x-control": {
                    legend: t("Did this answer the question?"),
                    enum: [QnAStatus.ACCEPTED, QnAStatus.REJECTED, QnAStatus.PENDING],
                    inputType: "radio",
                    choices: {
                        staticOptions: {
                            [QnAStatus.ACCEPTED]: t("Yes"),
                            [QnAStatus.REJECTED]: t("No"),
                            [QnAStatus.PENDING]: t("Don't know"),
                        },
                    },
                },
            },
        },
    };

    const patchAnswerStatus = usePatchAnswerStatus(comment);

    const { values, submitForm, setValues, isSubmitting } = useFormik<IFormValues>({
        initialValues: {
            status: comment.attributes?.answer.status ?? QnAStatus.PENDING,
        },
        onSubmit: async (values) => {
            try {
                await patchAnswerStatus.mutateAsync(values.status);
                !!onSuccess && (await onSuccess?.());
            } catch (e) {
                addToast({
                    autoDismiss: false,
                    dismissible: true,
                    body: <>{e.description ?? e.message}</>,
                });
            }
        },
        enableReinitialize: true,
    });

    return (
        <form
            role="form"
            onSubmit={async (e) => {
                e.preventDefault();
                await submitForm();
            }}
        >
            <Frame
                header={<FrameHeader closeFrame={onCancel} title={t("Change Status")} />}
                body={
                    <FrameBody>
                        <div className={classesFrameBody.contents}>
                            <JsonSchemaForm
                                schema={schema}
                                instance={values}
                                FormControl={FormControl}
                                FormControlGroup={FormControlGroup}
                                onChange={setValues}
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
                            disabled={isSubmitting}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                            className={classFrameFooter.actionButton}
                            type={"submit"}
                        >
                            {isSubmitting ? <ButtonLoader /> : t("Save")}
                        </Button>
                    </FrameFooter>
                }
            />
        </form>
    );
}
