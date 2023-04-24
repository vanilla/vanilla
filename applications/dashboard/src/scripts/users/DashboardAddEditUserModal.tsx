/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */
import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import dashboardAddEditUserClasses from "@dashboard/users/DashboardAddEditUser.classes";
import DashboardAddEditUserForm from "@dashboard/users/DashboardAddEditUserForm";
import { cx } from "@emotion/css";
import { IError } from "@library/errorPages/CoreErrorMessages";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ErrorMessages from "@library/forms/ErrorMessages";
import { ErrorIcon } from "@library/icons/common";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Message from "@library/messages/Message";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@vanilla/i18n";
import { IFieldError, IJsonSchemaFormHandle, JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import React, { useLayoutEffect, useRef } from "react";

interface IDashboardAddEditUserModalProps
    extends Omit<React.ComponentProps<typeof DashboardAddEditUserForm>, "setNeedsReload"> {
    handleSubmit: () => Promise<void>;
    setValues: React.Dispatch<React.SetStateAction<JsonSchema>>;
    values: JsonSchema;
    topLevelErrors: IError[];
    fieldErrors: Record<string, IFieldError[]>;
    isSubmitting: boolean;
}

export default function DashboardAddEditUserModal(props: IDashboardAddEditUserModalProps) {
    const {
        handleSubmit,
        schema,
        values,
        formGroupWrapper,
        newPasswordFieldID,
        topLevelErrors,
        fieldErrors,
        setValues,
        setIsVisible,
        isSubmitting,
        isVisible,
        title,
    } = props;
    const classes = dashboardAddEditUserClasses(newPasswordFieldID);
    const schemaFormRef = useRef<IJsonSchemaFormHandle | null>(null);
    const scrollRef = useRef<HTMLDivElement>(null);
    const titleID = `${title}_modal`;

    useLayoutEffect(() => {
        if (topLevelErrors && topLevelErrors.length > 0) {
            scrollRef.current?.scrollIntoView({ behavior: "smooth" });
        }
    }, [topLevelErrors]);

    return (
        <Modal
            isVisible={isVisible}
            size={ModalSizes.XL}
            exitHandler={() => {
                setIsVisible(false);
            }}
            titleID={titleID}
        >
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    handleSubmit();
                }}
                noValidate
                className={classes.modalForm}
            >
                <Frame
                    header={
                        <FrameHeader
                            closeFrame={() => {
                                setIsVisible(false);
                            }}
                            title={t(title ?? "Add/Edit User")}
                        />
                    }
                    body={
                        <FrameBody>
                            <div ref={scrollRef}></div>
                            <div className={cx(frameBodyClasses().contents)}>
                                {topLevelErrors && topLevelErrors.length > 0 && (
                                    <Message
                                        type="error"
                                        stringContents={topLevelErrors[0].message}
                                        icon={<ErrorIcon />}
                                        contents={<ErrorMessages errors={topLevelErrors} />}
                                    />
                                )}
                                <JsonSchemaForm
                                    fieldErrors={fieldErrors}
                                    schema={schema}
                                    instance={values}
                                    FormControlGroup={DashboardFormControlGroup}
                                    FormControl={DashboardFormControl}
                                    onChange={setValues}
                                    FormGroupWrapper={formGroupWrapper}
                                    ref={schemaFormRef}
                                />
                            </div>
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter justifyRight={true}>
                            <Button
                                className={frameFooterClasses().actionButton}
                                buttonType={ButtonTypes.TEXT}
                                onClick={() => {
                                    setIsVisible(false);
                                }}
                                disabled={isSubmitting}
                            >
                                {t("Cancel")}
                            </Button>
                            <Button
                                disabled={isSubmitting}
                                className={frameFooterClasses().actionButton}
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                                submit
                            >
                                {isSubmitting ? <ButtonLoader /> : t("Save")}
                            </Button>
                        </FrameFooter>
                    }
                />
            </form>
        </Modal>
    );
}
