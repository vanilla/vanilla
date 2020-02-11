/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { uploadFile } from "@library/apiv2";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardImageUpload } from "@dashboard/forms/DashboardImageUpload";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { t } from "@vanilla/i18n";
import { IFieldError } from "@library/@types/api/core";
import ModalConfirm from "@library/modal/ModalConfirm";

interface IProps {
    // Controlled props.
    value?: string | null; // The image url
    onChange?: (newUrl: string | null) => void;

    // Legacy input Props
    fieldName?: string;
    initialValue?: string;

    // Common props.
    label: string;
    description?: React.ReactNode;
    imageUploader?: typeof uploadFile;
    disabled?: boolean;
    errors?: IFieldError[];
}

export function DashboardImageUploadGroup(props: IProps) {
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [ownValue, ownOnChange] = useState<string | null>(props.initialValue || null);
    const [wantsDelete, setWantsDelete] = useState(false);

    const value = props.value ?? ownValue;
    const onChange = props.onChange ?? ownOnChange;

    const [originalValue] = useState(value);

    const imagePreviewSrc = previewUrl || value;
    const isStillOriginalValue = originalValue === value;
    const undoTitle = isStillOriginalValue ? t("Delete") : t("Undo");

    return (
        <>
            <DashboardFormGroup
                label={props.label}
                description={props.description}
                afterDescription={
                    imagePreviewSrc && (
                        <>
                            <div>{<img src={imagePreviewSrc} />}</div>
                            <div>
                                <Button
                                    baseClass={ButtonTypes.TEXT_PRIMARY}
                                    onClick={() => {
                                        setPreviewUrl(null);
                                        if (isStillOriginalValue) {
                                            setWantsDelete(true);
                                        } else {
                                            onChange(originalValue);
                                        }
                                    }}
                                    disabled={props.disabled}
                                >
                                    {undoTitle}
                                </Button>
                            </div>
                        </>
                    )
                }
            >
                {props.fieldName && <input type="hidden" value={value || ""} name={props.fieldName} />}
                <DashboardImageUpload
                    value={value}
                    onChange={onChange}
                    onImagePreview={setPreviewUrl}
                    imageUploader={props.imageUploader}
                    disabled={props.disabled}
                    errors={props.errors}
                />
            </DashboardFormGroup>
            <ModalConfirm
                isVisible={wantsDelete}
                title={t("Confirm Deletion")}
                onConfirm={() => {
                    onChange("");
                    setWantsDelete(false);
                }}
                onCancel={() => {
                    setWantsDelete(false);
                }}
            >
                {t("Are you sure you want to delete this image? You won't be able to recover it.")}
            </ModalConfirm>
        </>
    );
}
