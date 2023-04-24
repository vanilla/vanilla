/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { ComponentProps, ReactEventHandler, useEffect, useState } from "react";
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
import { IFieldError, JsonSchema } from "@vanilla/json-schema-forms";
import { ILayoutCatalog, IWidgetCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import isEmpty from "lodash/isEmpty";

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

    const validateAndSaveForm = (evt: React.FormEvent<HTMLFormElement>) => {
        evt.preventDefault();

        const schema: JsonSchema = widgetOrAssetCatalog[widgetID]?.schema ?? {};
        const errors: Record<string, IFieldError[]> = {};

        const checkFields = (tmpValue: any, tmpSchema: JsonSchema, path?: string[]) => {
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

    return (
        <Modal
            isVisible={isVisible}
            size={ModalSizes.LARGE}
            exitHandler={exitHandler}
            titleID={titleID}
            className={classes.container}
        >
            <form onSubmit={validateAndSaveForm} className={classes.modalForm}>
                <Frame
                    header={
                        <FrameHeader
                            titleID={titleID}
                            closeFrame={exitHandler}
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
                                onClick={exitHandler}
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
