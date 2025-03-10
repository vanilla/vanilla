/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useEffect, useRef } from "react";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import classNames from "classnames";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { t } from "@vanilla/i18n";
import { uploadFile } from "@library/apiv2";
import { IFieldError } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";
import { cx } from "@emotion/css";
import { dashboardImageUploadClasses } from "@dashboard/forms/DashboardImageUpload.classes";
import { useDashboardFormStyle } from "@dashboard/forms/DashboardFormStyleContext";

interface IProps {
    value: string | null; // The image url
    onChange: (newUrl: string) => void;
    onImagePreview?: (tempImageUrl: string) => void;
    className?: string;
    placeholder?: string;
    imageUploader?: typeof uploadFile;
    disabled?: boolean;
    errors?: IFieldError[];
    preview?: React.ReactNode;
}

export function DashboardImageUpload(props: IProps) {
    const { inputID } = useFormGroup();
    const imageUploader = props.imageUploader || uploadFile;
    const [isLoading, setIsLoading] = useState(false);
    const [name, setName] = useState<string | null>(null);
    const [uploadError, setUploadError] = useState<Error | null>(null);

    // Used for stashing the URL we just uploaded so we don't wipe out our name in the next step.
    const valueRef = useRef<string | null>(null);

    useEffect(() => {
        if (valueRef.current !== props.value) {
            setName(null);
        }
    }, [props.value]);

    const fallbackName = props.value?.substring(props.value?.lastIndexOf("/") + 1);
    const classes = dashboardImageUploadClasses();
    const formStyle = useDashboardFormStyle();

    return (
        <DashboardInputWrap>
            <label className={cx("file-upload", classes.fileUpload, { isCompact: formStyle.compact })}>
                <input
                    key={`${isLoading}`}
                    type="file"
                    id={inputID}
                    className={cx("form-control", props.className)}
                    disabled={props.disabled}
                    onChange={async (event) => {
                        const file = event.target.files && event.target.files[0];
                        if (!file) {
                            return;
                        }

                        setIsLoading(true);
                        setName(file.name);
                        const tempUrl = URL.createObjectURL(file);
                        props.onImagePreview && props.onImagePreview(tempUrl);

                        // Upload the image.
                        try {
                            setUploadError(null);
                            const uploaded = await imageUploader(file);
                            valueRef.current = uploaded.url;
                            props.onChange(uploaded.url);
                            setIsLoading(false);
                        } catch (e) {
                            setUploadError(e);
                            setIsLoading(false);
                        }
                    }}
                />
                <span className="file-upload-choose">{name || fallbackName || props.placeholder || t("Choose")}</span>
                <span className="file-upload-browse">
                    {isLoading ? <ButtonLoader buttonType={ButtonTypes.DASHBOARD_PRIMARY} /> : t("Browse")}
                </span>
            </label>
            {props.preview}
            {props.errors && <ErrorMessages errors={props.errors} />}
            {uploadError && (
                <ErrorMessages errors={[{ message: uploadError.message, code: "UploadError", field: "" }]} />
            )}
        </DashboardInputWrap>
    );
}
