/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import InputTextBlock from "@library/forms/InputTextBlock";
import { normalizeUrl } from "@library/utility/appUtils";
import { queryRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/queries/queryRichLink";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { IVanillaLinkElement } from "@library/vanilla-editor/typescript";
import { useMyEditorRef } from "@library/vanilla-editor/getMyEditor";
import { focusEditor, isUrl, setNodes } from "@udecode/plate-common";
import { floatingLinkActions, submitFloatingLink, useFloatingLinkSelectors } from "@udecode/plate-link";
import { t } from "@vanilla/i18n";
import { useFormik } from "formik";
import React, { useEffect, useRef, useState } from "react";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { richLinkFormClasses } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/RichLinkForm.classes";
import { cx } from "@emotion/css";

export default function LinkForm() {
    const editor = useMyEditorRef();
    const floatingLinkSelectors = useFloatingLinkSelectors();

    const updated = floatingLinkSelectors.updated();
    const firstInputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (firstInputRef.current && updated) {
            // Timeout needed or the form doesn't position itself properly.
            // If you can figure it out more power to you. :)
            setTimeout(() => {
                firstInputRef.current?.focus();
            }, 0);
        }
    }, [updated]);

    const url = floatingLinkSelectors.url();
    const text = floatingLinkSelectors.text();
    const embed = queryRichLink(editor);

    const { submitForm, setFieldValue, values, errors, submitCount } = useFormik({
        initialValues: {
            url,
            text,
        },
        onSubmit: function () {
            if (!embed || embed.appearance === RichLinkAppearance.LINK) {
                // Use the built-in submit and upsert.
                submitFloatingLink(editor);
            } else {
                floatingLinkActions.hide();
                setNodes<IVanillaLinkElement>(
                    editor,
                    {
                        url,
                        embedData: null,
                    },
                    { at: embed?.path },
                );
                focusEditor(editor);
            }
        },

        validate: ({ url, text }) => {
            setLastSubmittedUrlValue(url);

            if (!isUrl(normalizeUrl(url))) {
                return {
                    url: t("Link must be valid."),
                };
            }
        },
        validateOnChange: false,
    });

    const [lastSubmittedUrlValue, setLastSubmittedUrlValue] = useState(values.url);
    const displayUrlErrors = submitCount > 0 && lastSubmittedUrlValue === values.url;

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                e.stopPropagation();
                e.nativeEvent.stopImmediatePropagation();
                void submitForm();
            }}
            // This as well as the button click handler are wired up
            // For the old /post/discussion page which has a jquery form handler runs before our submit handler
            // Can stop propagation.
            // If we don't do this, then full discussion for will submit when we hit enter.
            onKeyDown={(e) => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    e.stopPropagation();
                    e.nativeEvent.stopImmediatePropagation();
                    void submitForm();
                }
            }}
        >
            <InputTextBlock
                label={t("URL")}
                inputProps={{
                    inputRef: firstInputRef,
                    onChange: (e) => {
                        void setFieldValue("url", e.target.value);
                        floatingLinkActions.url(normalizeUrl(e.target.value));
                    },
                    value: values.url,
                }}
                extendErrorMessage
                errors={displayUrlErrors && errors.url ? [{ message: errors.url }] : undefined}
            />

            {![RichLinkAppearance.CARD, RichLinkAppearance.INLINE].includes(
                embed?.appearance ?? RichLinkAppearance.LINK,
            ) && (
                <InputTextBlock
                    label={t("Text to Display")}
                    inputProps={{
                        onChange: (e) => {
                            void setFieldValue("text", e.target.value);
                            floatingLinkActions.text(e.target.value);
                        },
                        value: values.text,
                    }}
                />
            )}

            <hr className={cx(dropDownClasses().separator, richLinkFormClasses().separator)} />

            <Button
                buttonType={ButtonTypes.TEXT_PRIMARY}
                onClick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    e.nativeEvent.stopImmediatePropagation();
                    void submitForm();
                }}
                type="submit"
                className={richLinkFormClasses().addLinkButton}
            >
                {t("Add Link")}
            </Button>
        </form>
    );
}
