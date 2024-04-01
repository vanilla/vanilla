/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { useToast } from "@library/features/toaster/ToastContext";
import Heading from "@library/layout/Heading";
import { PageBox } from "@library/layout/PageBox";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { t } from "@vanilla/i18n";
import { INotificationPreferences } from "@library/notificationPreferences";
import { notificationPreferencesFormClasses } from "@library/preferencesTable/PreferencesTable.styles";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { FormControl, FormControlGroup } from "@library/forms/FormControl";
import { useNotificationPreferencesContext } from "@library/notificationPreferences";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import { useLocales } from "@library/config/configHooks";
import { useFormik } from "formik";

export function EmailLanguagePreferencesImpl(props: {
    localeOptions: IComboBoxOption[] | undefined;
    preferences?: INotificationPreferences;
    editPreferences?: (
        preferences: INotificationPreferences,
        options?: {
            onSuccess?: (data: INotificationPreferences) => void;
            onError?: (error: Error) => void;
        },
    ) => Promise<INotificationPreferences>;
}) {
    const { localeOptions, preferences, editPreferences } = props;
    const formClasses = notificationPreferencesFormClasses();
    const toast = useToast();

    const { values, setValues, submitForm } = useFormik<{
        NotificationLanguage: string;
    }>({
        initialValues: {
            NotificationLanguage: (preferences?.NotificationLanguage as string) ?? localeOptions?.[0]?.value ?? "",
        },
        enableReinitialize: true,
        onSubmit: async (vals) => {
            await editPreferences?.(vals, {
                onSuccess: () => {
                    toast.addToast({
                        autoDismiss: true,
                        body: <>{t("Success! Your changes were saved.")}</>,
                    });
                },
                onError: (e) => {
                    toast.addToast({
                        dismissible: true,
                        body: <>{t(e.message)}</>,
                    });
                },
            });
        },
    });

    if (!localeOptions || !localeOptions.length || localeOptions.length < 2) {
        return null;
    }

    const schema: JsonSchema = {
        type: "object",
        properties: {
            NotificationLanguage: {
                type: "string",
                "x-control": {
                    label: t("Email Language"),
                    description: t(
                        "This is the language your email notifications and digest will appear in. It won't affect what language posts were created in.",
                    ),
                    inputType: "dropDown",
                    choices: {
                        staticOptions: Object.fromEntries(
                            localeOptions.map((option: IComboBoxOption) => {
                                return [option.value, option.label];
                            }),
                        ),
                    },
                },
            },
        },
        required: ["NotificationLanguage"],
    };

    return (
        <PageBox
            options={{
                borderType: BorderType.SEPARATOR_BETWEEN,
            }}
            className={formClasses.selectContainer}
        >
            <Heading depth={2} title={t("Language")} />

            <PageBox className={formClasses.subgroupWrapper}>
                <JsonSchemaForm
                    schema={schema}
                    instance={values}
                    FormControl={FormControl}
                    FormControlGroup={FormControlGroup}
                    onChange={async (values) => {
                        setValues(values);
                        await submitForm();
                    }}
                />
            </PageBox>
        </PageBox>
    );
}

export default function EmailLanguagePreferences() {
    const { localeOptions } = useLocales();
    const { preferences, editPreferences } = useNotificationPreferencesContext();

    const dataIsReady = !!preferences?.data;

    if (dataIsReady) {
        return (
            <EmailLanguagePreferencesImpl
                localeOptions={localeOptions}
                preferences={preferences.data}
                editPreferences={editPreferences}
            />
        );
    } else {
        return null;
    }
}
