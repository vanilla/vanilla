/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { PostField } from "@dashboard/postTypes/postType.types";
import { fieldVisibilityAsOptions, formTypeAsOptions, mapFormTypeToDataType } from "@dashboard/postTypes/utils";
import { CreatableFieldFormType } from "@dashboard/userProfiles/types/UserProfiles.types";
import { css } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { SchemaFormBuilder } from "@library/json-schema-forms";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalConfirm from "@library/modal/ModalConfirm";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@vanilla/i18n";
import { slugify } from "@vanilla/utils";
import { useEffect, useState } from "react";

interface IProps {
    postField: PostField | null;
    isVisible: boolean;
    onConfirm: (newField: Partial<PostField>) => void;
    onCancel: () => void;
}

export function PostFieldEditModal(props: IProps) {
    const { postField, isVisible, onConfirm, onCancel } = props;

    const [values, setValues] = useState<Partial<PostField> | null>(postField);
    const [shouldCreateApiLabel, setShouldCreateApiLabel] = useState(!props.postField);
    const [showConfirmExit, setShowConfirmExit] = useState(false);
    const [isDirty, setIsDirty] = useState(false);

    useEffect(() => {
        if (isVisible && postField) {
            setValues(() => {
                return {
                    ...postField,
                    dropdownOptions: postField?.dropdownOptions?.join("\n"),
                } as Partial<PostField>; // This icky casting because we need to convert array options to a string for the form controls
            });
            setShouldCreateApiLabel(false);
        }
    }, [isVisible, postField]);

    const resetAndClose = () => {
        setValues(null);
        setIsDirty(false);
        onCancel();
    };

    const handleClose = () => {
        if (isDirty) {
            setShowConfirmExit(true);
        } else {
            setIsDirty(false);
            resetAndClose();
        }
    };

    const handleSubmit = () => {
        if (values) {
            const payload = {
                ...values,
                ...(values.formType && { dataType: mapFormTypeToDataType(values.formType) }),
                ...(values.dropdownOptions && {
                    dropdownOptions: `${values.dropdownOptions}`.split("\n"),
                }),
            };
            onConfirm(payload);
        }
        resetAndClose();
    };

    const schema = new SchemaFormBuilder()
        .textBox("label", "Field Label", "The name of the post field.")
        .required()
        .textBox(
            "postFieldID",
            "API Label",
            "The unique identifier for your custom field. Once selected, this cannot be changed.",
            !!props.postField,
            "[a-zA-Z0-9-_]",
        )
        .required()
        .textArea(
            "description",
            "Description",
            "Provide helpful information or guidelines to users creating a post of this type. ",
        )
        .dropdown("formType", "Form Type", "The type of form element to use for this field.", formTypeAsOptions())
        .required()
        .textArea("dropdownOptions", "Options", "A list of options for the dropdown field. One per line.")
        .withCondition({ field: "formType", enum: ["dropdown", "tokens"], default: "" })
        .required()
        .dropdown("visibility", "Visibility", "The visibility of the field.", fieldVisibilityAsOptions())
        .required()
        .checkBoxRight(
            "isRequired",
            "Required",
            "If this field must be populated in order to make a post of this type.",
        )
        .getSchema();

    return (
        <>
            <Modal
                isVisible={isVisible}
                size={ModalSizes.LARGE}
                exitHandler={() => handleClose}
                titleID={"editPostField"}
            >
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        handleSubmit();
                    }}
                >
                    <Frame
                        header={
                            <FrameHeader
                                closeFrame={handleClose}
                                title={postField ? t("Edit Post Field") : t("Add Post Field")}
                                titleID={"editPostField"}
                            />
                        }
                        body={
                            <FrameBody>
                                <DashboardSchemaForm
                                    schema={schema}
                                    instance={values}
                                    onChange={(values) => {
                                        setIsDirty(true);
                                        if (values()["postFieldID"]) {
                                            setShouldCreateApiLabel(false);
                                        }
                                        if (values()["label"] && shouldCreateApiLabel) {
                                            let newValues = values();
                                            newValues = {
                                                ...newValues,
                                                postFieldID: slugify(newValues["label"]),
                                            };
                                            setValues((prev) => ({ ...prev, ...newValues }));
                                        } else {
                                            setValues((prev) => ({ ...prev, ...values() }));
                                        }
                                    }}
                                />
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight={true}>
                                <Button
                                    className={frameFooterClasses().actionButton}
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={handleClose}
                                >
                                    {t("Cancel")}
                                </Button>
                                <Button
                                    submit
                                    className={frameFooterClasses().actionButton}
                                    buttonType={ButtonTypes.TEXT_PRIMARY}
                                >
                                    {postField ? t("Update") : t("Add")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>
            <ModalConfirm
                isVisible={showConfirmExit}
                title={t("Unsaved Changes")}
                onCancel={() => setShowConfirmExit(false)}
                onConfirm={() => {
                    setIsDirty(false);
                    setShowConfirmExit(false);
                    resetAndClose();
                }}
                confirmTitle={t("Discard")}
                bodyClassName={css({ justifyContent: "start" })}
            >
                {t("You have unsaved changes. Are you sure you want to exit without saving?")}
            </ModalConfirm>
        </>
    );
}
