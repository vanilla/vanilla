/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useEffect } from "react";
import { navigationLinksModalClasses as modalClasses } from "@dashboard/components/navigation/NavigationLinksModal.styles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { FormTreeControl } from "@library/tree/FormTreeControl";
import { t } from "@vanilla/i18n";
import { IControlProps } from "@vanilla/json-schema-forms";
import { IconType } from "@vanilla/icons";
import { useValidCounts } from "@library/siteTotalsWidget/SiteTotals";

export const SITE_TOTALS_AS_MODAL = {
    condition: (props: IControlProps): boolean => {
        return props.control.inputType === "modal" && props.rootSchema.description === "Site Totals";
    },
    callback: function SiteTotalsModalControl(props: IControlProps) {
        const validInstance = useValidCounts(props.instance, props.rootSchema);
        const [isOpen, setOpen] = useState(false);
        const classes = modalClasses();

        useEffect(() => {
            props.onChange(validInstance);
        }, []);

        function openModal() {
            setOpen(true);
        }

        function closeModal() {
            setOpen(false);
        }

        function getRowIcon(item): IconType {
            return "data-site-metric";
        }

        const control = props.control as any;
        const { description } = control.modalContent;

        return (
            <>
                <div className="input-wrap">
                    <Button onClick={openModal} buttonType={ButtonTypes.STANDARD}>
                        {control["modalTriggerLabel"]}
                    </Button>
                </div>
                <Modal isVisible={isOpen} size={ModalSizes.LARGE} exitHandler={closeModal}>
                    <Frame
                        header={<FrameHeader closeFrame={closeModal} title={control.modalContent.label} />}
                        body={
                            <FrameBody>
                                {description && <p className={classes.modalDescription}>{description}</p>}
                                <FormTreeControl
                                    {...{
                                        ...props,
                                        control: {
                                            ...control.modalContent,
                                            description: undefined,
                                        },
                                        getRowIcon,
                                    }}
                                />
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight={true}>
                                <Button
                                    className={classes.modalButton}
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={closeModal}
                                >
                                    {t("Cancel")}
                                </Button>
                                <Button
                                    className={classes.modalButton}
                                    buttonType={ButtonTypes.TEXT_PRIMARY}
                                    onClick={closeModal}
                                >
                                    {t("Apply")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </Modal>
            </>
        );
    },
};
