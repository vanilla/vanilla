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
import { useQuery } from "@tanstack/react-query";
import apiv2 from "@library/apiv2";

export function EmailLanguagePreferencesImpl(props: {
    value?: string;
    localeOptions: IComboBoxOption[] | undefined;
    onSubmit?: (vals: { language: string }) => Promise<void>;
}) {
    const { localeOptions, onSubmit, value: currentLanguage } = props;
    const formClasses = notificationPreferencesFormClasses();
    const toast = useToast();

    const { values, setValues, submitForm } = useFormik<{
        NotificationLanguage: string;
    }>({
        initialValues: {
            NotificationLanguage: currentLanguage ?? `${localeOptions?.[0]?.value ?? ""}`,
        },
        enableReinitialize: true,
        onSubmit: async (vals) => {
            try {
                await onSubmit?.({ language: vals.NotificationLanguage });
                toast.addToast({
                    autoDismiss: true,
                    body: <>{t("Success! Your changes were saved.")}</>,
                });
            } catch (e) {
                toast.addToast({
                    dismissible: true,
                    body: <>{t(e.message)}</>,
                });
            }
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
    const locales = useQuery({
        queryKey: ["locales"],
        queryFn: async () => {
            const response = await apiv2.get("/locales");
            return response.data;
        },
    });
    const { preferences, patchLanguage } = useNotificationPreferencesContext();

    const localeOptions = locales.data?.map((locale: any) => {
        return {
            value: locale.localeKey,
            label: locale.displayNames[locale.localeKey],
        };
    });
    const dataIsReady = !!preferences?.data;

    if (dataIsReady) {
        return (
            <EmailLanguagePreferencesImpl
                localeOptions={localeOptions}
                value={(preferences.data?.NotificationLanguage as string) ?? undefined}
                onSubmit={async ({ language }) => {
                    await patchLanguage(language);
                }}
            />
        );
    } else {
        return null;
    }
}
