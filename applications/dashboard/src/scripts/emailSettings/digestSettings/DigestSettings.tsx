/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useEffect, useRef, useCallback } from "react";
import { useRouteChangePrompt } from "@vanilla/react-utils";
import { JsonSchemaForm, IFieldError, IJsonSchemaFormHandle } from "@vanilla/json-schema-forms";
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
import { IEmailDigestSettings, IEmailSettings } from "@dashboard/emailSettings/EmailSettings.types";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@vanilla/i18n";
import { MemoryRouter } from "react-router";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { mapValidationErrorsToFormikErrors } from "@vanilla/json-schema-forms/src/utils";
import { getDigestSettingsSchemas, getEmailSettingsSchemas } from "@dashboard/emailSettings/EmailSettings.utils";
import { emailSettingsClasses } from "@dashboard/emailSettings/EmailSettings.classes";
import { cx } from "@emotion/css";

export function DigestSettings() {
    const DIGEST_SETTINGS_SCHEMA = getDigestSettingsSchemas().emailDigestSchema;
    const EMAIL_SETTINGS_SCHEMA = getEmailSettingsSchemas().emailSettingsSchema;
    const classes = emailSettingsClasses();
    const schemaFormRef = useRef<IJsonSchemaFormHandle | null>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, any>>({});

    const settings = useConfigsByKeys(
        Object.keys({
            ...DIGEST_SETTINGS_SCHEMA["properties"],
            ...EMAIL_SETTINGS_SCHEMA["properties"],
        }),
    );
    const isLoaded = [LoadStatus.SUCCESS, LoadStatus.ERROR].includes(settings.status);
    const wasLoaded = useLastValue(isLoaded);
    const { isLoading: isPatchLoading, patchConfig, error } = useConfigPatcher();
    const [showTestDigestModal, setShowTestDigestModal] = useState<boolean>(false);

    const [settingsLoaded, setSettingsLoaded] = useState<boolean>(false);

    const [emailSettings, setEmailSettings] = useState<IEmailSettings | {}>(
        Object.keys(EMAIL_SETTINGS_SCHEMA.properties).reduce((acc, currentKey) => {
            const value = EMAIL_SETTINGS_SCHEMA.properties[currentKey];
            return {
                ...acc,
                [currentKey]: value.type === "boolean" ? false : value.type === "number" ? 1 : "",
            };
        }, {}),
    );

    // if errors are present, scroll to the first error
    useEffect(() => {
        const errorElement = document.querySelector('[id^="errorMessages"]');
        if (errorElement) {
            errorElement?.scrollIntoView({ behavior: "smooth" });
        }
    }, [fieldErrors]);

    useEffect(() => {
        // Initialize the values we just loaded.
        if (!wasLoaded && isLoaded && settings.data) {
            setValues((existing) => ({
                ...existing,
                ...Object.fromEntries(
                    Object.keys(DIGEST_SETTINGS_SCHEMA.properties).map((key) => {
                        // this will prevent schema validation error for emailDigest.metaOptions, which is only to render "Meta Options" as header
                        if (key === "emailDigest.metaOptions") {
                            return [key, settings.data[key] === "" ? {} : settings.data[key] ?? {}];
                        }

                        return [key, settings.data[key] ?? DIGEST_SETTINGS_SCHEMA.properties[key].default ?? ""];
                    }),
                ),
            }));
            setEmailSettings((existing) => ({
                ...existing,
                ...Object.fromEntries(
                    Object.keys(EMAIL_SETTINGS_SCHEMA.properties).map((key) => {
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

    const sections = ["General", "Content"];

    const [isFormEdited, setIsFormEdited] = useState<boolean>(false);
    const [disabledRouteChangePrompt, setDisableRouteChangePrompt] = useState<boolean>(true);
    useRouteChangePrompt(
        t(
            "You are leaving the Email Settings page without saving your changes. Make sure your updates are saved before exiting.",
        ),
        disabledRouteChangePrompt,
    );

    const { values, setValues, submitForm } = useFormik<IEmailDigestSettings>({
        initialValues: Object.keys(DIGEST_SETTINGS_SCHEMA.properties).reduce((acc, currentKey) => {
            const value = DIGEST_SETTINGS_SCHEMA.properties[currentKey];
            return {
                ...acc,
                [currentKey]: value?.default ?? null,
            };
        }, {}) as IEmailDigestSettings,

        onSubmit: async function (values) {
            try {
                await patchConfig(
                    Object.fromEntries(
                        Object.keys(values)
                            .map((key) => {
                                if (key === "emailDigest.footer" || key === "emailDigest.introduction") {
                                    return [
                                        key,
                                        typeof values[key] === "string" ? values[key] : JSON.stringify(values[key]),
                                    ];
                                }
                                return [key, values[key]];
                            })
                            .filter((entry) => entry[0] !== "emailDigest.metaOptions"),
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
                                DIGEST_SETTINGS_SCHEMA.properties[instanceLocation].minimum ??
                                DIGEST_SETTINGS_SCHEMA.properties[instanceLocation].min ??
                                0;
                            errorRecord.error = t("Out of range. Minimum is ") + minimum;
                        }
                        if (errorRecord.keyword === "maximum") {
                            let maximum = DIGEST_SETTINGS_SCHEMA.properties[instanceLocation].maximum ?? 0;
                            errorRecord.error = t("Exceeded limit. Maximum is ") + maximum;
                        }
                    }
                });
            }
            const mappedErrors = mapValidationErrorsToFormikErrors(result?.errors ?? []);
            setFieldErrors(mappedErrors);
            return mappedErrors ?? {};
        },
        validateOnChange: false,
    });

    // some handling for line breaks for title text area field
    useEffect(() => {
        const titleField = document.getElementById("emailDigest-title");
        if (titleField) {
            // someText.replace(/(\r\n|\n|\r)/gm, "");
            titleField.setAttribute("pattern", "^[^\\s\\n\\r]*$");
        }
    }, [values]);

    return (
        <MemoryRouter>
            <form
                role="form"
                onSubmit={(e) => {
                    e.preventDefault();
                    submitForm();
                }}
                className={classes.root}
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
                            <div
                                key={index}
                                ref={(ele) => addToRefs(ele, index)}
                                className={cx(classes.section, { [classes.contentSection]: section === "Content" })}
                            >
                                <DashboardFormSubheading hasBackground>
                                    {t(section)}
                                    {section === "General" && (
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
                                    fieldErrors={error?.errors ?? fieldErrors ?? {}}
                                    schema={
                                        section === "General"
                                            ? getDigestSettingsSchemas().emailDigesGeneralSchema
                                            : getDigestSettingsSchemas().emailDigestContentSchema
                                    }
                                    instance={values}
                                    FormControlGroup={DashboardFormControlGroup}
                                    FormControl={DashboardFormControl}
                                    FormGroupWrapper={(props) => {
                                        return (
                                            <li
                                                className={cx("form-group", "meta-group-header", {
                                                    [classes.hidden]: !props.rootInstance["emailDigest.enabled"],
                                                })}
                                            >
                                                {props.header && (
                                                    <span className={classes.metaGroupHeader}>{props.header}</span>
                                                )}
                                                {props.children}
                                            </li>
                                        );
                                    }}
                                    ref={schemaFormRef}
                                    onChange={(newValue) => {
                                        // some handling for line breaks for title text area field
                                        if (newValue["emailDigest.title"] !== values["emailDigest.title"]) {
                                            newValue = {
                                                ...newValue,
                                                "emailDigest.title": newValue["emailDigest.title"].replace(
                                                    /(\r\n|\n|\r)/gm,
                                                    "",
                                                ),
                                            };
                                        }

                                        setValues(newValue);
                                        if (isFormEdited) {
                                            setDisableRouteChangePrompt(false);
                                        }
                                        setIsFormEdited(true);
                                    }}
                                />

                                {section === "General" && values["emailDigest.enabled"] && (
                                    <DigestSchedule dayOfWeek={values["emailDigest.dayOfWeek"]} />
                                )}
                            </div>
                        ))}
                </DashboardFormList>
            </form>
            <DashboardHelpAsset>
                <h3 className={classes.uppercase}>{t("Quicklinks")}</h3>
                <div className={classes.quickLinks}>
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
                </div>

                <h3 className={classes.uppercase}>{t("About Email Digest")}</h3>
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
