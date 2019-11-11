/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useEffect, useRef } from "react";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroup";
import classNames from "classnames";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { t } from "@vanilla/i18n";
import { uploadFile } from "@library/apiv2";

interface IProps {
    value: string | null; // The image url
    onChange: (newUrl: string) => void;
    onImagePreview?: (tempImageUrl: string) => void;
    className?: string;
    placeholder?: string;
    imageUploader?: typeof uploadFile;
    disabled?: boolean;
}

export function DashboardImageUpload(props: IProps) {
    const { inputID, labelType } = useFormGroup();
    const imageUploader = props.imageUploader || uploadFile;
    const [name, setName] = useState<string | null>(null);

    // Used for stashing the URL we just uploaded so we don't wipe out our name in the next step.
    const valueRef = useRef<string | null>(null);

    useEffect(() => {
        if (valueRef.current !== props.value) {
            setName(null);
        }
    }, [props.value]);

    const classes = classNames("form-control", props.className);
    const rootClass = labelType === DashboardLabelType.WIDE ? "input-wrap-right" : "input-wrap";

    return (
        <div className={rootClass}>
            <label className="file-upload">
                <input
                    type="file"
                    id={inputID}
                    className={classes}
                    disabled={props.disabled}
                    onChange={async event => {
                        const file = event.target.files && event.target.files[0];
                        if (!file) {
                            return;
                        }

                        setName(file.name);
                        const tempUrl = URL.createObjectURL(file);
                        props.onImagePreview && props.onImagePreview(tempUrl);

                        // Upload the image.
                        const uploaded = await imageUploader(file);
                        valueRef.current = uploaded.url;
                        props.onChange(uploaded.url);
                    }}
                />
                <span className="file-upload-choose">{name || props.placeholder || t("Choose")}</span>
                <span className="file-upload-browse">{t("Browse")}</span>
            </label>
        </div>
    );
}
