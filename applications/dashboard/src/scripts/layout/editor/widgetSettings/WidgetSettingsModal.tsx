/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { ComponentProps, useEffect, useState } from "react";
import classNames from "classnames";
import { t } from "@vanilla/i18n";
import { useUniqueID } from "@library/utility/idUtils";
import LazyModal from "@library/modal/LazyModal";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Button from "@library/forms/Button";
import ModalSizes from "@library/modal/ModalSizes";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import Modal from "@library/modal/Modal";
import { WidgetSettingsPreview } from "@dashboard/layout/editor/widgetSettings/WidgetSettingsPreview";
import { widgetSettingsClasses } from "@dashboard/layout/editor/widgetSettings/WidgetSettings.classes";
import { WidgetSettings } from "@dashboard/layout/editor/widgetSettings/WidgetSettings";
import { IWidgetCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { JsonSchema } from "@vanilla/json-schema-forms";

interface IProps {
    title?: string;
    onSave: (newConfig: any) => void;
    isVisible: ComponentProps<typeof Modal>["isVisible"];
    exitHandler: ComponentProps<typeof Modal>["exitHandler"];
    widgetSchema: JsonSchema;
    initialValue?: any;
}

export function WidgetSettingsModal(props: IProps) {
    const { title, onSave, isVisible, exitHandler, widgetSchema, initialValue } = props;
    const classes = widgetSettingsClasses();
    const classFrameFooter = frameFooterClasses();
    const classesFrameBody = frameBodyClasses();
    const titleID = useUniqueID("widgetSettings_Modal");
    const [value, setValue] = useState(initialValue ?? {});

    useEffect(() => {
        setValue(initialValue ?? {});
    }, [initialValue]);

    return (
        <>
            <LazyModal
                isVisible={isVisible}
                size={ModalSizes.LARGE}
                exitHandler={exitHandler}
                titleID={titleID}
                className={classes.container}
            >
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        onSave(value);
                    }}
                    className={classes.modalForm}
                >
                    <Frame
                        header={<FrameHeader titleID={titleID} closeFrame={exitHandler} title={t("Add/Edit Widget")} />}
                        body={
                            <section
                                className={classNames("frameBody-contents", classesFrameBody.contents, classes.section)}
                            >
                                <WidgetSettingsPreview />
                                <WidgetSettings value={value} onChange={setValue} schema={widgetSchema} />
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
            </LazyModal>
        </>
    );
}
