/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

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
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@vanilla/i18n";
import { JSONSchemaType, JsonSchemaForm, extractSchemaDefaults } from "@vanilla/json-schema-forms";
import { useFormik } from "formik";
import React from "react";

export default function NotifyInterestedUsersDialog(props: {
    title: string;
    isVisible: React.ComponentProps<typeof Modal>["isVisible"];
    exitHandler: React.ComponentProps<typeof Modal>["exitHandler"];
    onSubmit: (notify: boolean) => Promise<void>;
}) {
    const { title, isVisible, exitHandler, onSubmit } = props;

    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();

    const choiceOptions = {
        true: t("Yes, send updates"),
        false: t("No, do not send updates"),
    };

    interface IFormValues {
        notify: "true" | "false";
    }

    const schema: JSONSchemaType<IFormValues> = {
        type: "object",
        properties: {
            notify: {
                type: "string",
                enum: Object.keys(choiceOptions),
                default: "true",
                "x-control": {
                    inputType: "radio",
                    legend: t("Do you want to notify interested users of the update?"),
                    choices: {
                        staticOptions: choiceOptions,
                    },
                },
            },
        },
        required: [],
    };

    const initialValues = extractSchemaDefaults(schema) as IFormValues;

    const { values, submitForm, isSubmitting, setValues } = useFormik<IFormValues>({
        initialValues,
        onSubmit: async function (values) {
            await onSubmit(values.notify === "true");
        },
    });

    return (
        <Modal isVisible={isVisible} size={ModalSizes.MEDIUM} exitHandler={exitHandler}>
            <form
                role="form"
                onSubmit={async (e) => {
                    e.preventDefault();
                    await submitForm();
                }}
            >
                <Frame
                    header={<FrameHeader closeFrame={exitHandler} title={title} />}
                    body={
                        <FrameBody>
                            <div className={classesFrameBody.contents}>
                                <JsonSchemaForm
                                    schema={schema}
                                    FormControl={FormControl}
                                    FormControlGroup={FormControlGroup}
                                    instance={values}
                                    onChange={setValues}
                                />
                            </div>
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter justifyRight>
                            <Button
                                buttonType={ButtonTypes.TEXT}
                                onClick={exitHandler}
                                className={classFrameFooter.actionButton}
                            >
                                {t("Cancel")}
                            </Button>
                            <Button
                                submit
                                disabled={isSubmitting}
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                                className={classFrameFooter.actionButton}
                            >
                                {isSubmitting ? <ButtonLoader /> : t("OK")}
                            </Button>
                        </FrameFooter>
                    }
                />
            </form>
        </Modal>
    );
}
