/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { FragmentsApi } from "@dashboard/appearance/fragmentEditor/FragmentsApi";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { css, cx } from "@emotion/css";
import Button from "@library/forms/Button";
import InputBlock from "@library/forms/InputBlock";
import InputTextBlock from "@library/forms/InputTextBlock";
import { SchemaFormBuilder } from "@library/json-schema-forms";
import ButtonLoader from "@library/loaders/ButtonLoader";
import type { UseMutationResult } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { useEffect, useRef, useState } from "react";

interface IProps {
    saveMutation: UseMutationResult<any, any, FragmentsApi.CommitData>;
    className?: string;
}

export function FragmentEditorCommitForm(props: IProps) {
    const { saveMutation, className } = props;
    const [form, setForm] = useState<FragmentsApi.CommitData>({
        commitMessage: "",
        commitDescription: "",
    });

    const messageRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        messageRef.current?.focus();
    }, []);

    return (
        <form
            className={cx(classes.commitForm, className)}
            onSubmit={(e) => {
                e.preventDefault();

                if (saveMutation.isLoading) {
                    return;
                }

                saveMutation.mutate(form);
            }}
        >
            <InputTextBlock
                inputProps={{
                    inputRef: messageRef,
                    value: form.commitMessage,
                    onChange: (e) => setForm({ ...form, commitMessage: e.target.value }),
                    required: true,
                    "aria-label": t("Commit Message"),
                    placeholder: t("Commit Message (Required)"),
                }}
            />
            <InputTextBlock
                inputProps={{
                    multiline: true,
                    value: form.commitDescription,
                    onChange: (e) => setForm({ ...form, commitDescription: e.target.value }),
                    "aria-label": t("Commit Description"),
                    placeholder: t("Commit Description"),
                }}
                multiLineProps={{
                    rows: 3,
                }}
            />

            <Button className={classes.submitButton} submit buttonType={"primary"} disabled={saveMutation.isLoading}>
                <span className={classes.submitButtonContents}>
                    {t("Commit")}
                    {saveMutation.isLoading && <ButtonLoader />}
                </span>
            </Button>
        </form>
    );
}

const classes = {
    commitForm: css({
        padding: "0 16px",
    }),
    submitButton: css({
        width: "100%",
        minHeight: 30,
        marginTop: 16,
    }),
    submitButtonContents: css({
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
    }),
};
