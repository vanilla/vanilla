/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardFormControlGroup, DashboardFormControl } from "@dashboard/forms/DashboardFormControl";
import { useReasonMutation } from "@dashboard/moderation/CommunityManagement.hooks";
import { IReason } from "@dashboard/moderation/CommunityManagementTypes";
import { IError } from "@library/errorPages/CoreErrorMessages";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Modal from "@library/modal/Modal";
import ModalConfirm from "@library/modal/ModalConfirm";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@vanilla/i18n";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { logDebug, slugify } from "@vanilla/utils";
import cloneDeep from "lodash-es/cloneDeep";
import isEqual from "lodash-es/isEqual";
import { useEffect, useMemo, useRef, useState } from "react";
import ErrorMessages from "@library/forms/ErrorMessages";
import Message from "@library/messages/Message";
import { ErrorIcon } from "@library/icons/common";
import Translate from "@library/content/Translate";
import { css } from "@emotion/css";

interface IProps {
    reportReason: IReason | null;
    isVisible: boolean;
    onVisibilityChange: (visible: boolean) => void;
}

const REASON_SCHEMA: JsonSchema = {
    type: "object",
    properties: {
        name: {
            type: "string",
            default: "",
            "x-control": {
                label: t("Name"),
                description: t("A concise name for the report reason."),
                inputType: "textBox",
            },
        },
        reportReasonID: {
            type: "string",
            default: "",
            "x-control": {
                label: t("API Name"),
                description: t("A unique label for the report reason. This label cannot be changed once saved."),
                inputType: "textBox",
                pattern: "[^' ']+",
            },
        },
        description: {
            type: "string",
            default: "",
            "x-control": {
                label: t("Description"),
                description: t(
                    "An explanation of the report reason to better reporters make the correct selection when reporting a post.",
                ),
                inputType: "textBox",
                type: "textarea",
            },
        },
        roleIDs: {
            type: "string",
            default: "",
            "x-control": {
                label: t("Roles"),
                description: t("The roles which can see this report reason."),
                inputType: "dropDown",
                placeholder: "",
                choices: {
                    api: {
                        searchUrl: "/api/v2/roles",
                        singleUrl: "/api/v2/roles/%s",
                        valueKey: "roleID",
                        labelKey: "name",
                    },
                },
                multiple: true,
                tooltip:
                    "If no roles are specified, all roles with the `flag.add` permission for a given category can see this reason. Users with `community.moderate` permission can use every reason all the time.",
            },
        },
    },
    required: ["name", "description"],
};

// TODO: Move this to a utility function
const schemaModifier = (schema: JsonSchema, override: Record<string, Partial<JsonSchema>>): JsonSchema => {
    let newSchema = cloneDeep(schema);
    if (!schema) {
        logDebug("Schema is not provided.");
        return schema;
    }
    if (!override) {
        logDebug("Override is not provided.");
        return schema;
    }
    const overrideKeys = Object.keys(override);
    overrideKeys.forEach((key) => {
        newSchema.properties[key] = { ...newSchema.properties[key], ...override[key] };
    });
    return newSchema;
};

const errorContainer = css({
    padding: "16px 0",
});

export default function AddEditReportReasonModalImp(props: IProps) {
    const { reportReason, isVisible, onVisibilityChange } = props;
    const reasonMutation = useReasonMutation(true);
    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();

    const [confirmDialogVisible, setConfirmDialogVisible] = useState(false);
    const [isDirty, setIsDirty] = useState(false);
    const [values, setValues] = useState({});
    const [reportReasonIDTouched, setReportReasonIDTouched] = useState(false);

    // We need initial values to compare for dirty state
    let initial = reportReason
        ? {
              name: reportReason.name,
              description: reportReason.description,
              reportReasonID: reportReason.reportReasonID,
          }
        : {};

    // Schema might need modification based on the context
    const schema = reportReason
        ? schemaModifier(REASON_SCHEMA, {
              reportReasonID: {
                  disabled: true,
              },
          })
        : REASON_SCHEMA;

    // Set initial values when editing
    useEffect(() => {
        if (reportReason) {
            setValues(initial);
        }
    }, [reportReason]);

    // Check if the form is dirty
    useEffect(() => {
        if (!isEqual(values, initial)) {
            setIsDirty(true);
        }
    }, [values]);

    const serverErrorMessage = useMemo<IError[] | null>(() => {
        if (reasonMutation.error?.response?.data) {
            const messages: IError[] = [];
            if (!Array.isArray(reasonMutation.error.response.data)) {
                messages.push({ message: reasonMutation.error.response.data.message });
            }
            if (reasonMutation.error?.response?.data.conflictingReason) {
                messages.push({
                    message: `${t("The conflicting reason is:")} "${
                        reasonMutation.error.response.data.conflictingReason.name
                    } - ${reasonMutation.error.response.data.conflictingReason.description}"}`,
                });
                if (reasonMutation.error.response.data.conflictingReason.deleted) {
                    messages.push({
                        message: t(
                            "The conflicting reason is deleted and has reports or escalations associated with it.",
                        ),
                    });
                }
            }
            return messages;
        }
        return null;
    }, [reasonMutation.error]);

    const handleCloseButton = () => {
        if (isDirty) {
            setConfirmDialogVisible(true);
        } else {
            resetAndClose();
        }
    };

    const resetAndClose = () => {
        initial = {};
        setValues({});
        setIsDirty(false);
        setConfirmDialogVisible(false);
        onVisibilityChange(false);
        reasonMutation.reset();
    };

    const submitForm = async () => {
        await reasonMutation.mutateAsync({ reason: values, reportReasonID: reportReason?.reportReasonID });
        resetAndClose();
        onVisibilityChange(false);
    };

    const generateReportReasonID = () => {
        if (!reportReason) {
            if (values?.["name"] && !reportReasonIDTouched) {
                setValues((prev) => ({
                    ...prev,
                    reportReasonID: slugify(values["name"]),
                }));
            }
        }
    };

    const autoFixError = () => {
        const descriptionFragment = (values["description"] ?? "vanilla forums").split(" ").slice(0, 2).join("-");
        setValues((prev) => ({
            ...prev,
            reportReasonID: slugify(`${values["name"]}-${descriptionFragment}`),
        }));
    };

    return (
        <>
            <Modal
                isVisible={isVisible}
                exitHandler={() => handleCloseButton()}
                size={ModalSizes.LARGE}
                titleID={"reasonConfiguration"}
            >
                <form
                    onSubmit={async (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        await submitForm();
                    }}
                >
                    <Frame
                        header={
                            <FrameHeader
                                titleID={"reasonConfiguration"}
                                closeFrame={() => handleCloseButton()}
                                title={reportReason ? t("Edit Report Reason") : t("Add Report Reason")}
                            />
                        }
                        body={
                            <FrameBody className={classesFrameBody.root}>
                                {serverErrorMessage && (
                                    <div className={errorContainer}>
                                        <Message
                                            type="error"
                                            stringContents={
                                                typeof serverErrorMessage[0].message === "string"
                                                    ? serverErrorMessage[0].message
                                                    : "Validation Error"
                                            }
                                            icon={<ErrorIcon />}
                                            contents={<ErrorMessages errors={serverErrorMessage} />}
                                            confirmText={t("Fix error")}
                                            onConfirm={() => autoFixError()}
                                        />
                                    </div>
                                )}
                                <JsonSchemaForm
                                    disabled={reasonMutation.isLoading}
                                    fieldErrors={reasonMutation.error?.response?.data?.errors}
                                    schema={schema}
                                    instance={values}
                                    FormControlGroup={DashboardFormControlGroup}
                                    FormControl={DashboardFormControl}
                                    onBlur={(e) => {
                                        if (e === "name") {
                                            generateReportReasonID();
                                        }
                                    }}
                                    onChange={(values) => {
                                        setValues(values);
                                        if (values.reportReasonID && !reportReasonIDTouched) {
                                            setReportReasonIDTouched(true);
                                        }
                                    }}
                                />
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight>
                                <>
                                    <Button
                                        buttonType={ButtonTypes.TEXT}
                                        onClick={() => handleCloseButton()}
                                        className={classFrameFooter.actionButton}
                                    >
                                        {t("Cancel")}
                                    </Button>
                                    <Button
                                        submit
                                        disabled={reasonMutation.isLoading}
                                        buttonType={ButtonTypes.TEXT_PRIMARY}
                                    >
                                        {reasonMutation.isLoading ? <ButtonLoader /> : t("Save")}
                                    </Button>
                                </>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>

            <ModalConfirm
                isVisible={confirmDialogVisible}
                title={t("Discard Changes?")}
                onCancel={() => setConfirmDialogVisible(false)}
                onConfirm={() => resetAndClose()}
                confirmTitle={t("Exit")}
            >
                {t("Are you sure you want to exit without saving?")}
            </ModalConfirm>
        </>
    );
}
