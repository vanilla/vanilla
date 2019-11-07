/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { IUploadedMedia, uploadFile } from "@library/apiv2";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardImageUpload } from "@dashboard/forms/DashboardImageUpload";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { t } from "@vanilla/i18n";

interface IProps {
    value: string | null; // The image url
    onChange: (newUrl: string) => void;
    label: string;
    description?: React.ReactNode;
    imageUploader?: typeof uploadFile;
}

export function DashboardImageUploadGroup(props: IProps) {
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [originalValue] = useState(props.value);

    const imagePreviewSrc = previewUrl || props.value;

    return (
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
                                    props.onChange(originalValue);
                                }}
                            >
                                {t("Undo")}
                            </Button>
                        </div>
                    </>
                )
            }
        >
            <DashboardImageUpload
                value={props.value}
                onChange={props.onChange}
                onImagePreview={setPreviewUrl}
                imageUploader={props.imageUploader}
            />
        </DashboardFormGroup>
    );
}
