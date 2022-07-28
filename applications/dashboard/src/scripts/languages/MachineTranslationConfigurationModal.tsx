/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import { ITranslationService } from "@dashboard/languages/LanguageSettingsTypes";
import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n";
import { JsonSchemaForm } from "@vanilla/json-schema-forms";
import React, { useEffect, useMemo, useState } from "react";

export interface IProps {
    isVisible: boolean;
    service: ITranslationService | null;
    onExit(): void;
    setConfiguration(newConfig: any): void;
    modalSize?: ModalSizes; // Will need this for the language config
}

export const MachineTranslationConfigurationModal = (props: IProps) => {
    const { isVisible, onExit, service, setConfiguration } = props;
    const titleID = useUniqueID("configureLanguage_Modal");
    const classFrameFooter = frameFooterClasses();
    const classesFrameBody = frameBodyClasses();
    const [value, setValue] = useState({});
    const [modalSize, setModalSize] = useState(props.modalSize ?? ModalSizes.MEDIUM);

    useEffect(() => {
        if (service) {
            setValue(() =>
                Object.keys(service.configSchema.properties).reduce(
                    (obj, key) => ({ ...obj, [key]: service[key] }),
                    {},
                ),
            );
        }
    }, [service]);

    useEffect(() => {
        if (service) {
            setModalSize(() => {
                return Object.keys(service.configSchema.properties).length > 1 ? ModalSizes.LARGE : ModalSizes.MEDIUM;
            });
        }
        if (props.modalSize) {
            setModalSize(props.modalSize);
        }
    }, [props.modalSize, service]);

    return (
        <Modal
            isVisible={isVisible}
            size={modalSize}
            exitHandler={() => {
                onExit();
            }}
            titleID={titleID}
        >
            <Frame
                header={
                    <FrameHeader
                        titleID={titleID}
                        closeFrame={() => {
                            onExit();
                        }}
                        title={service && service.name}
                    />
                }
                body={
                    service &&
                    service.configSchema && (
                        <FrameBody>
                            <div className={cx("frameBody-contents", classesFrameBody.contents)}>
                                <JsonSchemaForm
                                    schema={service && service.configSchema}
                                    instance={value}
                                    onChange={setValue}
                                    FormControlGroup={DashboardFormControlGroup}
                                    FormControl={DashboardFormControl}
                                />
                            </div>
                        </FrameBody>
                    )
                }
                footer={
                    <FrameFooter justifyRight={true}>
                        <Button
                            className={classFrameFooter.actionButton}
                            buttonType={ButtonTypes.TEXT}
                            onClick={() => {
                                onExit();
                            }}
                        >
                            {t("Cancel")}
                        </Button>
                        <Button
                            className={classFrameFooter.actionButton}
                            onClick={() => {
                                service && setConfiguration(value);
                            }}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                        >
                            {t("Save")}
                        </Button>
                    </FrameFooter>
                }
            />
        </Modal>
    );
};
