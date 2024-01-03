/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { t } from "@vanilla/i18n";
import ModalConfirm from "@library/modal/ModalConfirm";
import ModalSizes from "@library/modal/ModalSizes";

interface IProps {
    fieldName: string;
    initialValue: boolean;
    label: string;
    description?: string;
    modal?: {
        content?: string;
        title?: string;
    };
    isDashboardSection?: boolean; // depending on this, we might want to use a different component in the future
}

export default function ToggleInputInLegacyForm(props: IProps) {
    const { fieldName, initialValue, modal, isDashboardSection } = props;
    const [isVisible, setIsVisible] = useState(false);
    const [checked, setChecked] = useState(initialValue);

    return (
        <>
            {isDashboardSection && (
                <DashboardFormGroup
                    labelType={DashboardLabelType.WIDE}
                    label={t(props.label)}
                    description={props.description}
                >
                    <DashboardToggle
                        checked={checked}
                        onChange={() => {
                            !checked && setIsVisible(true);
                            setChecked(!checked);
                        }}
                        name={fieldName}
                    />
                </DashboardFormGroup>
            )}
            {modal && (
                <ModalConfirm
                    isVisible={isVisible}
                    onCancel={() => {
                        setIsVisible(false);
                        setChecked(false);
                    }}
                    onConfirm={() => {
                        setIsVisible(false);
                    }}
                    title={modal.title ?? ""}
                    size={ModalSizes.LARGE}
                >
                    <div dangerouslySetInnerHTML={{ __html: modal.content ?? "" }}></div>
                </ModalConfirm>
            )}
        </>
    );
}
