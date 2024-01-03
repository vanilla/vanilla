/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useEffect, useRef, useCallback } from "react";
import { useRouteChangePrompt } from "@vanilla/react-utils";
import { JsonSchemaForm, JSONSchemaType, IFieldError, IJsonSchemaFormHandle } from "@vanilla/json-schema-forms";
import { useFormik } from "formik";
import { useLastValue } from "@vanilla/react-utils";
import { LoadStatus } from "@library/@types/api/core";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import TestDigestModal from "@dashboard/emailSettings/components/TestDigestModal";
import DigestSchedule from "@dashboard/emailSettings/components/DigestSchedule";
import { IEmailDigestSettings, IEmailSettings } from "@dashboard/emailSettings/emailSettingsTypes";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@vanilla/i18n";
import { MemoryRouter } from "react-router";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { mapValidationErrorsToFormikErrors } from "@vanilla/json-schema-forms/src/utils";
import { getDigestSettingsSchemas, getEmailSettingsSchemas } from "@dashboard/emailSettings/emailSettingsUtils";

const EMAIL_DIGEST_SECTION = "Email Digest";

export function DigestSettings() {
    const EMAIL_DIGEST_SCHEMA = getDigestSettingsSchemas();

    const DIGEST_SETTINGS: JSONSchemaType<IEmailDigestSettings> = {
        type: "object",
        properties: {
            ...EMAIL_DIGEST_SCHEMA.properties,
        },
    };

    const EMAIL_SETTINGS: JSONSchemaType<IEmailSettings> = {
        type: "object",
        properties: {
            ...getEmailSettingsSchemas().emailSettingsSchema.properties,
        },
    };

    const schemaFormRef = useRef<IJsonSchemaFormHandle | null>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]>>({});

    const settings = useConfigsByKeys(
        Object.keys({
            ...DIGEST_SETTINGS["properties"],
            ...getEmailSettingsSchemas().emailSettingsSchema["properties"],
        }),
    );
    const isLoaded = [LoadStatus.SUCCESS, LoadStatus.ERROR].includes(settings.status);
    const wasLoaded = useLastValue(isLoaded);
    const { isLoading: isPatchLoading, patchConfig, error } = useConfigPatcher();
    const [showTestDigestModal, setShowTestDigestModal] = useState<boolean>(false);

    const [settingsLoaded, setSettingsLoaded] = useState<boolean>(false);

    const [emailSettings, setEmailSettings] = useState<IEmailSettings | {}>(
        Object.keys(getEmailSettingsSchemas().emailSettingsSchema.properties).reduce((acc, currentKey) => {
            const value = getEmailSettingsSchemas().emailSettingsSchema.properties[currentKey];
            return {
                ...acc,
                [currentKey]: value.type === "boolean" ? false : value.type === "number" ? 1 : "",
            };
        }, {}),
    );

    useEffect(() => {
        // Initialize the values we just loaded.
        if (!wasLoaded && isLoaded && settings.data) {
            setValues((existing) => ({
                ...existing,
                ...Object.fromEntries(
                    Object.keys(DIGEST_SETTINGS.properties).map((key) => {
                        return [key, settings.data[key] ?? DIGEST_SETTINGS.properties[key].default ?? ""];
                    }),
                ),
            }));
            setEmailSettings((existing) => ({
                ...existing,
                ...Object.fromEntries(
                    Object.keys(EMAIL_SETTINGS.properties).map((key) => {
                        if (key === "emailNotifications.disabled") {
                            return [key, !settings.data[key]];
                        } else if (key === "emailStyles.format") {
                            return [key, settings.data[key] === "html" ? true : false];
                        }
                        return [key, settings.data[key] ?? ""];
                    }),
                ),
            }));
            setSettingsLoaded(true);
        }
    }, [wasLoaded, isLoaded, settings.data]);

    const scrollRefs = useRef<HTMLDivElement[]>([]);

    const addToRefs = useCallback((el: HTMLDivElement | null, index: number) => {
        if (!el || scrollRefs.current.includes(el)) return;
        scrollRefs.current.splice(index, 0, el);
    }, []);

    const scrollToRef = (index) => {
        scrollRefs.current?.[index]?.scrollIntoView({ behavior: "smooth" });
    };

    let sections = [EMAIL_DIGEST_SECTION];

    const [isFormEdited, setIsFormEdited] = useState<boolean>(false);
    const [disabledRouteChangePrompt, setDisableRouteChangePrompt] = useState<boolean>(true);
    useRouteChangePrompt(
        t(
            "You are leaving the Email Settings page without saving your changes. Make sure your updates are saved before exiting.",
        ),
        disabledRouteChangePrompt,
    );

    const { values, setValues, submitForm, validateForm } = useFormik<IEmailDigestSettings>({
        initialValues: Object.keys(DIGEST_SETTINGS.properties).reduce((acc, currentKey) => {
            const value = DIGEST_SETTINGS.properties[currentKey];
            return {
                ...acc,
                [currentKey]: value?.default ?? null,
            };
        }, {}) as IEmailDigestSettings,

        onSubmit: async function (values) {
            try {
                await patchConfig(
                    Object.fromEntries(
                        Object.keys(values).map((key) => {
                            return [key, values[key]];
                        }),
                    ) as IEmailDigestSettings,
                );
                setDisableRouteChangePrompt(true);
            } catch (e) {
                if (e.errors) {
                    setFieldErrors(e.errors);
                }
            }
        },
        validate: () => {
            const result = schemaFormRef?.current?.validate();
            if (result?.valid == false) {
                Object.keys(result.errors).forEach((key, index) => {
                    let errorRecord = result.errors[index];
                    if (errorRecord.instanceLocation !== "#") {
                        let instanceLocation = errorRecord.instanceLocation.replace("#/", "");
                        if (errorRecord.keyword === "minimum") {
                            let minimum =
                                DIGEST_SETTINGS.properties[instanceLocation].minimum ??
                                DIGEST_SETTINGS.properties[instanceLocation].min ??
                                0;
                            errorRecord.error = t("Out of range. Minimum is ") + minimum;
                        }
                        if (errorRecord.keyword === "maximum") {
                            let maximum = DIGEST_SETTINGS.properties[instanceLocation].maximum ?? 0;
                            errorRecord.error = t("Exceeded limit. Maximum is ") + maximum;
                        }
                    }
                });
            }
            const mappedErrors = mapValidationErrorsToFormikErrors(result?.errors ?? []);
            return mappedErrors ?? {};
        },
        validateOnChange: false,
    });

    return (
        <MemoryRouter>
            <form
                role="form"
                onSubmit={async (e) => {
                    e.preventDefault();
                    await submitForm();
                }}
                noValidate
            >
                <DashboardHeaderBlock
                    title={t("Digest Settings")}
                    actionButtons={
                        <Button
                            buttonType={ButtonTypes.DASHBOARD_PRIMARY}
                            disabled={isPatchLoading || !isLoaded}
                            submit
                        >
                            {t("Save")}
                        </Button>
                    }
                />

                <DashboardFormList>
                    {settingsLoaded &&
                        sections.map((section, index) => (
                            <div key={index} ref={(ele) => addToRefs(ele, index)} style={{ scrollMarginTop: 96 }}>
                                <DashboardFormSubheading hasBackground>
                                    {t(section)}
                                    {section === EMAIL_DIGEST_SECTION && (
                                        <DropDown name={t("Email Digest Options")} flyoutType={FlyoutType.LIST}>
                                            <DropDownItemButton
                                                name={t("Send Test Digest")}
                                                onClick={() => {
                                                    setShowTestDigestModal(true);
                                                }}
                                            />
                                        </DropDown>
                                    )}
                                </DashboardFormSubheading>

                                <JsonSchemaForm
                                    disabled={!isLoaded}
                                    fieldErrors={error?.errors ?? {}}
                                    schema={EMAIL_DIGEST_SCHEMA}
                                    instance={values}
                                    FormControlGroup={DashboardFormControlGroup}
                                    FormControl={DashboardFormControl}
                                    ref={schemaFormRef}
                                    onChange={(newValue) => {
                                        setValues(newValue);
                                        if (isFormEdited) {
                                            setDisableRouteChangePrompt(false);
                                        }
                                        setIsFormEdited(true);
                                    }}
                                />

                                {section === EMAIL_DIGEST_SECTION && values["emailDigest.enabled"] && (
                                    <DigestSchedule dayOfWeek={values["emailDigest.dayOfWeek"]} />
                                )}
                            </div>
                        ))}
                </DashboardFormList>
            </form>
            <DashboardHelpAsset>
                <h3>{t("Quicklinks")}</h3>
                {sections.map((section, index) => (
                    <Button
                        key={section}
                        buttonType={ButtonTypes.TEXT}
                        onClick={(e) => {
                            e.preventDefault();
                            scrollToRef(index);
                        }}
                    >
                        {t(section)}
                    </Button>
                ))}

                <h3>{t("ABOUT EMAIL DIGEST")}</h3>
                <p>
                    {t(
                        "Styles and general visual appearance of all emails, including email digest, are set site-wide on Email Settings page.",
                    )}
                </p>
                <p>{t("Email Digest Prompt Widget will be available when the Email Digest is enabled.")}</p>
            </DashboardHelpAsset>

            {showTestDigestModal && (
                <TestDigestModal settings={emailSettings} onCancel={() => setShowTestDigestModal(false)} />
            )}
        </MemoryRouter>
    );
}
