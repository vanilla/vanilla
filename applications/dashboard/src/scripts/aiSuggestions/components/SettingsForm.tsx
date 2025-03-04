/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useSaveAISuggestionsSettings } from "@dashboard/aiSuggestions/AISuggestions.hooks";
import { AISuggestionsSettings, AISuggestionsSettingsForm } from "@dashboard/aiSuggestions/AISuggestions.types";
import { AISuggestionSectionSchema, getInitialSettings } from "@dashboard/aiSuggestions/settingsSchemaUtils";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardCheckBox } from "@dashboard/forms/DashboardCheckBox";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import Translate from "@library/content/Translate";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n";
import { IFieldError } from "@vanilla/json-schema-forms";
import { useFormik } from "formik";
import { Fragment, useState } from "react";
import { DurationPickerUnit } from "@library/forms/durationPicker/DurationPicker.types";

interface IProps {
    title: string;
    sections: AISuggestionSectionSchema[];
    settings?: AISuggestionsSettings;
}

export function SettingsForm(props: IProps) {
    const { sections, title, settings } = props;
    const { mutateAsync } = useSaveAISuggestionsSettings();
    const toast = useToast();
    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]> | undefined>(undefined);

    const { values, setValues, submitForm, setFieldValue } = useFormik<AISuggestionsSettingsForm>({
        initialValues: {
            ...getInitialSettings(sections),
            enabled: settings?.enabled ?? false,
            delay: settings?.delay ?? { unit: DurationPickerUnit.DAYS, length: 0 },
        },
        onSubmit: async (formValues) => {
            setFieldErrors(undefined);
            try {
                await mutateAsync(formValues);
                toast.addToast({
                    autoDismiss: true,
                    body: t("Settings saved successfully"),
                });
            } catch (err: any) {
                setFieldErrors(err.errors);
                toast.addToast({
                    dismissible: true,
                    body: t(err.message),
                });
            }
        },
    });

    return (
        <form
            onSubmit={async (event) => {
                event.preventDefault();
                await submitForm();
            }}
        >
            <DashboardHeaderBlock
                title={title}
                actionButtons={
                    <Button buttonType={ButtonTypes.DASHBOARD_PRIMARY} submit>
                        {t("Save")}
                    </Button>
                }
            />
            <DashboardFormList>
                <DashboardFormSubheading hasBackground>{t("General")}</DashboardFormSubheading>
                <li>
                    <DashboardCheckBox
                        label={t("Use AI Suggestions in Q&A")}
                        description={
                            <Translate
                                source="When enabled, your community will have AI Suggestions shown in Q&A. <0/>"
                                c0={
                                    <SmartLink to="https://success.vanillaforums.com/kb/articles/1606-ai-suggested-answers">
                                        {t("Learn more.")}
                                    </SmartLink>
                                }
                            />
                        }
                        checked={values.enabled}
                        onChange={(val) => setFieldValue("enabled", val)}
                    />
                </li>
                <DashboardFormList isBlurred={!values.enabled}>
                    {sections.map(({ title, schema }, idx) => (
                        <Fragment key={`section-${idx + 1}`}>
                            <DashboardFormSubheading hasBackground>{title}</DashboardFormSubheading>
                            <DashboardSchemaForm
                                instance={values}
                                schema={schema}
                                onChange={setValues}
                                fieldErrors={fieldErrors}
                            />
                        </Fragment>
                    ))}
                </DashboardFormList>
            </DashboardFormList>
        </form>
    );
}
