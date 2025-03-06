/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { ComponentProps, useState } from "react";
import classNames from "classnames";
import { t } from "@vanilla/i18n";
import { useUniqueID } from "@library/utility/idUtils";
import Modal from "@library/modal/Modal";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Button from "@library/forms/Button";
import ModalSizes from "@library/modal/ModalSizes";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { WidgetSettingsPreview } from "@dashboard/layout/editor/widgetSettings/WidgetSettingsPreview";
import { widgetSettingsClasses } from "@dashboard/layout/editor/widgetSettings/WidgetSettings.classes";
import { WidgetSettings } from "@dashboard/layout/editor/widgetSettings/WidgetSettings";
import { IFieldError, JsonSchema, PartialSchemaDefinition } from "@vanilla/json-schema-forms";
import { ILayoutCatalog, IWidgetCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { useFormik } from "formik";
import ModalConfirm from "@library/modal/ModalConfirm";
import { useIsMounted } from "@vanilla/react-utils";

export interface IWidgetConfigurationComponentProps {
    schema: JsonSchema;
    value: any;
    onChange: (newValue: any) => void;
    middlewares: ILayoutCatalog["middlewares"];
}

interface IProps {
    onSave: (newConfig: any) => void;
    isVisible: ComponentProps<typeof Modal>["isVisible"];
    exitHandler: ComponentProps<typeof Modal>["exitHandler"];
    widgetID: string;
    initialValues: any;
    widgetCatalog: IWidgetCatalog;
    middlewaresCatalog: ILayoutCatalog["middlewares"];
    assetCatalog?: IWidgetCatalog;
    schema: JsonSchema;
    name: string;
}

export function WidgetSettingsModal(props: IProps) {
    const {
        schema,
        name,
        widgetID,
        onSave,
        isVisible,
        exitHandler,
        initialValues,
        widgetCatalog,
        middlewaresCatalog,
        assetCatalog,
    } = props;

    const classes = widgetSettingsClasses();
    const classFrameFooter = frameFooterClasses();
    const classesFrameBody = frameBodyClasses();
    const titleID = useUniqueID("widgetSettings_Modal");

    const [confirmDialogVisible, setConfirmDialogVisible] = useState(false);
    const isMounted = useIsMounted();

    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]>>({});

    // we will need to use our schema validator here at some point, ticket for that created here https://higherlogic.atlassian.net/browse/VNLA-5028
    const checkFields = (tmpValue: any, tmpSchema: JsonSchema | PartialSchemaDefinition, path?: string[]) => {
        const errors: Record<string, IFieldError[]> = {};
        const requiredProps = tmpSchema.required ?? [];
        requiredProps.forEach((fieldName) => {
            const fieldPath = path ? [...path] : [];
            fieldPath.push(fieldName);
            const fieldSchema = tmpSchema.properties ? tmpSchema.properties[fieldName] : tmpSchema;

            if (tmpValue && fieldSchema?.properties) {
                checkFields(tmpValue[fieldName], fieldSchema, fieldPath);
            } else if (!tmpValue) {
                errors[fieldPath.join("/")] = [
                    {
                        field: fieldName,
                        message: t("Invalid entry."),
                        path: path?.join("/"),
                    },
                ];
            }
        });

        const limitSchemas = [
            tmpSchema.properties.limit,
            tmpSchema.properties.apiParams?.properties?.limit,
            tmpSchema.properties.authorBadges?.properties?.limit,
            tmpSchema.properties?.suggestedFollows?.properties?.limit,
            tmpSchema.properties?.suggestedContent?.properties?.limit,
        ];

        // authorBadges is not in API params, we control the number in FE only
        const limitsToCheck = [
            tmpValue.limit,
            tmpValue.apiParams?.limit,
            tmpValue.authorBadges?.limit,
            tmpValue?.suggestedFollows?.limit,
            tmpValue?.suggestedContent?.limit,
        ];

        const limitPaths = ["limit", "apiParams", "authorBadges", "suggestedFollows", "suggestedContent"];

        limitsToCheck.forEach((limitToCheck, i) => {
            const limitSchema = limitSchemas[i];

            const currentLimitExceedsMaximum =
                limitSchema && limitSchema.maximum && limitToCheck && parseInt(limitToCheck) > limitSchema.maximum;

            if (currentLimitExceedsMaximum) {
                const errorMessage = `${t("Number input must be between")} ${limitSchemas[i].minimum} ${t("and")} ${
                    limitSchemas[i].maximum
                }.`;

                errors[`${limitPaths[i]}/limit`] = [
                    {
                        field: "limit",
                        message: errorMessage,
                        path: limitPaths[i],
                    },
                ];
            }
        });

        return errors;
    };

    function close() {
        setFieldErrors({});
        setTimeout(() => {
            if (isMounted()) {
                exitHandler && exitHandler();
            }
        }, 1);
    }

    const { values, setValues, submitForm, dirty, resetForm } = useFormik({
        initialValues,
        enableReinitialize: true,
        onSubmit: (values) => {
            onSave(values);
            close();
        },
        validateOnChange: false,
        validateOnMount: false,
        validate: (values) => {
            const errors = checkFields(values, schema);
            setFieldErrors(errors);
            return errors;
        },
    });

    function handleClose() {
        if (dirty) {
            if (isMounted()) {
                setConfirmDialogVisible(true);
            }
        } else {
            setConfirmDialogVisible(false);
            close();
        }
    }

    return (
        <Modal
            isVisible={isVisible}
            size={ModalSizes.XXL}
            exitHandler={handleClose}
            titleID={titleID}
            className={classes.container}
        >
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    void submitForm();
                }}
                className={classes.modalForm}
            >
                <Frame
                    header={
                        <FrameHeader
                            titleID={titleID}
                            closeFrame={handleClose}
                            title={`${t("Add/Edit")} ${name ?? ""}`}
                        />
                    }
                    body={
                        <section className={classNames(classesFrameBody.contents, classes.section)}>
                            <WidgetSettingsPreview
                                widgetID={widgetID}
                                config={values}
                                value={values}
                                widgetCatalog={widgetCatalog}
                                onChange={setValues}
                                schema={schema}
                                middlewares={middlewaresCatalog}
                                assetCatalog={assetCatalog}
                            />
                            <WidgetSettings
                                value={values}
                                onChange={setValues}
                                schema={schema}
                                middlewares={middlewaresCatalog}
                                fieldErrors={fieldErrors}
                            />
                        </section>
                    }
                    footer={
                        <FrameFooter justifyRight={true}>
                            <Button
                                className={classFrameFooter.actionButton}
                                buttonType={ButtonTypes.TEXT}
                                onClick={handleClose}
                            >
                                {t("Cancel")}
                            </Button>
                            <Button
                                submit
                                className={classFrameFooter.actionButton}
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                            >
                                {t("Save")}
                            </Button>
                        </FrameFooter>
                    }
                />
            </form>

            <ModalConfirm
                title={t("Unsaved Changes")}
                isVisible={confirmDialogVisible}
                onCancel={() => {
                    setConfirmDialogVisible(false);
                }}
                onConfirm={() => {
                    resetForm({ values: initialValues });
                    setConfirmDialogVisible(false);
                    close();
                }}
                confirmTitle={t("Exit")}
            >
                {t(
                    "You are leaving the widget editor without saving your changes. Make sure your updates are saved before exiting.",
                )}
            </ModalConfirm>
        </Modal>
    );
}
