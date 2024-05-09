/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { ComponentProps, useEffect, useState } from "react";
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
import isEmpty from "lodash-es/isEmpty";

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
    initialValue?: any;
    widgetCatalog: IWidgetCatalog;
    middlewaresCatalog: ILayoutCatalog["middlewares"];
    assetCatalog?: IWidgetCatalog;
}

export function WidgetSettingsModal(props: IProps) {
    const { widgetID, onSave, isVisible, exitHandler, initialValue, widgetCatalog, middlewaresCatalog, assetCatalog } =
        props;

    const classes = widgetSettingsClasses();
    const classFrameFooter = frameFooterClasses();
    const classesFrameBody = frameBodyClasses();
    const titleID = useUniqueID("widgetSettings_Modal");
    const [value, setValue] = useState<any>(initialValue ?? {});
    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]>>({});

    useEffect(() => {
        setValue(initialValue ?? {});
    }, [initialValue]);

    const widgetOrAssetCatalog = assetCatalog && assetCatalog[widgetID] ? assetCatalog : widgetCatalog;

    // we will need to use our schema validator here at some point, ticket for that created here https://higherlogic.atlassian.net/browse/VNLA-5028
    const validateAndSaveForm = (evt: React.FormEvent<HTMLFormElement>) => {
        evt.preventDefault();

        const schema: JsonSchema = widgetOrAssetCatalog[widgetID]?.schema ?? {};
        const errors: Record<string, IFieldError[]> = {};

        const checkFields = (tmpValue: any, tmpSchema: JsonSchema | PartialSchemaDefinition, path?: string[]) => {
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

            const limitSchema = tmpSchema.properties.limit ?? tmpSchema.properties.apiParams?.properties?.limit;
            const limitToCheck = tmpValue.limit ?? tmpValue.apiParams?.limit;
            const currentLimitExceedsMaximum =
                limitSchema && limitSchema.maximum && limitToCheck && parseInt(limitToCheck) > limitSchema.maximum;
            if (currentLimitExceedsMaximum) {
                errors[tmpSchema.properties.limit ? "limit" : "apiParams/limit"] = [
                    {
                        field: "limit",
                        message: t("Number input must be between 1 and 100."),
                        path: tmpSchema.properties.limit ? undefined : "apiParams",
                    },
                ];
            }
        };

        checkFields(value, schema);

        const isValid = isEmpty(errors);

        setFieldErrors(errors);

        if (isValid) {
            onSave(value);
        }
    };

    const changeValue = (newValue) => {
        setValue(newValue);
        setFieldErrors({});
    };

    const onExit = () => {
        setFieldErrors({});
        exitHandler && exitHandler();
    };

    return (
        <Modal
            isVisible={isVisible}
            size={ModalSizes.LARGE}
            exitHandler={onExit}
            titleID={titleID}
            className={classes.container}
        >
            <form onSubmit={validateAndSaveForm} className={classes.modalForm}>
                <Frame
                    header={
                        <FrameHeader
                            titleID={titleID}
                            closeFrame={onExit}
                            title={`${t("Add/Edit")} ${widgetOrAssetCatalog[widgetID]?.name ?? ""}`}
                        />
                    }
                    body={
                        <section className={classNames(classesFrameBody.contents, classes.section)}>
                            <WidgetSettingsPreview
                                widgetID={widgetID}
                                config={value}
                                widgetCatalog={widgetCatalog}
                                value={value}
                                onChange={changeValue}
                                schema={widgetOrAssetCatalog[widgetID]?.schema ?? {}}
                                middlewares={middlewaresCatalog}
                                assetCatalog={assetCatalog}
                            />
                            <WidgetSettings
                                value={value}
                                onChange={changeValue}
                                schema={widgetOrAssetCatalog[widgetID]?.schema ?? {}}
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
                                onClick={onExit}
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
        </Modal>
    );
}
