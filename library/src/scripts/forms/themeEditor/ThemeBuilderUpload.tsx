/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useRef } from "react";
import { useThemeBlock } from "@library/forms/themeEditor/ThemeBuilderBlock";
import { uploadFile } from "@library/apiv2";
import classNames from "classnames";
import { t } from "@vanilla/i18n";
import ButtonLoader from "@library/loaders/ButtonLoader";
import ErrorMessages from "@library/forms/ErrorMessages";
import { useThemeVariableField } from "@library/forms/themeEditor/ThemeBuilderContext";
import DropDown, { FlyoutType, DropDownOpenDirection } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { visibility } from "@library/styles/styleHelpers";
import { themeBuilderUploadClasses } from "@library/forms/themeEditor/ThemeBuilderUpload.styles";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";

interface IProps {
    variableKey: string;
    disabled?: boolean;
}

export function ThemeBuilderUpload(props: IProps) {
    const { disabled, variableKey } = props;

    const { rawValue, defaultValue, initialValue, error, setError, setValue } = useThemeVariableField<string>(
        variableKey,
    );

    const [previewImage, setPreviewImage] = useState<string | null>(null);
    const { inputID, labelID } = useThemeBlock();
    const [isLoading, setIsLoading] = useState(false);
    const classes = themeBuilderUploadClasses();
    const buttonRef = useRef<HTMLLabelElement>(null);

    return (
        <>
            <div className={classes.root}>
                <label
                    className={classes.button}
                    tabIndex={0}
                    ref={buttonRef}
                    onKeyDown={(e) => {
                        if (e.key === " ") {
                            buttonRef.current?.click();
                        }
                    }}
                    role="button"
                >
                    <input
                        key={`${isLoading}`}
                        className={classNames(visibility().visuallyHidden)}
                        aria-labelledby={labelID}
                        type="file"
                        id={inputID}
                        // className={classes}
                        disabled={disabled}
                        onChange={async (event) => {
                            const file = event.target.files && event.target.files[0];
                            if (!file) {
                                return;
                            }

                            setIsLoading(true);
                            const tempUrl = URL.createObjectURL(file);
                            setPreviewImage(tempUrl);

                            // Upload the image.
                            try {
                                const uploaded = await uploadFile(file);
                                setIsLoading(false);
                                setValue(uploaded.url);
                                setPreviewImage(null);
                            } catch (e) {
                                setPreviewImage(null);
                                setError(e.message);
                                setIsLoading(false);
                            }
                        }}
                    />
                    <span>{t("Choose Image")}</span>
                </label>
                <span className={classes.optionContainer}>
                    <span className={classes.imagePreviewContainer}>
                        {(previewImage ?? rawValue) && (
                            <img className={classes.imagePreview} src={previewImage ?? rawValue ?? undefined} />
                        )}
                    </span>
                    {isLoading ? (
                        <ButtonLoader />
                    ) : (
                        <DropDown
                            openDirection={DropDownOpenDirection.BELOW_LEFT}
                            flyoutType={FlyoutType.LIST}
                            buttonClassName={classes.optionButton}
                            contentsClassName={classes.optionDropdown}
                        >
                            <DropDownItemButton
                                disabled={!rawValue || rawValue === initialValue}
                                onClick={() => {
                                    setValue(initialValue);
                                    buttonRef.current?.focus();
                                }}
                            >
                                {t("Revert")}
                            </DropDownItemButton>
                            <DropDownItemButton
                                disabled={!rawValue}
                                onClick={() => {
                                    setValue(null);
                                    buttonRef.current?.focus();
                                }}
                            >
                                {t("Delete")}
                            </DropDownItemButton>
                        </DropDown>
                    )}
                </span>
            </div>
            {error && (
                <ErrorMessages
                    className={themeBuilderClasses().error}
                    errors={[{ message: error, code: "UploadError", field: "" }]}
                />
            )}
        </>
    );
}
