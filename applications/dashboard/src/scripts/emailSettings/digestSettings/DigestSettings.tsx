/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { emailSettingsClasses } from "@dashboard/emailSettings/EmailSettings.classes";
import { IEmailDigestSettings } from "@dashboard/emailSettings/EmailSettings.types";
import { getDigestSettingsSchemas, getEmailSettingsSchemas } from "@dashboard/emailSettings/EmailSettings.utils";
import DigestSchedule from "@dashboard/emailSettings/components/DigestSchedule";
import TestDigestModal from "@dashboard/emailSettings/components/TestDigestModal";
import { DashboardFormControlGroup, DashboardFormControl } from "@dashboard/forms/DashboardFormControl";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { cx } from "@emotion/css";
import { LoadStatus } from "@library/@types/api/core";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import Translate from "@library/content/Translate";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n";
import { IFieldError, IJsonSchemaFormHandle, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { validationErrorsToFieldErrors } from "@vanilla/json-schema-forms/src/utils";
import { useRouteChangePrompt } from "@vanilla/react-utils";
import isEqual from "lodash/isEqual";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { MemoryRouter } from "react-router";

export function DigestSettings() {
    // Schemas
    const DIGEST_SETTINGS_SCHEMA = getDigestSettingsSchemas().emailDigestSchema;
    const EMAIL_SETTINGS_SCHEMA = getEmailSettingsSchemas().emailSettingsSchema;

    const classes = emailSettingsClasses();

    const { isLoading: isPatchLoading, patchConfig, error } = useConfigPatcher();

    const sections = ["General", "Content"];

    // Grab saved values from the config
    const settings = useConfigsByKeys(
        Object.keys({
            ...DIGEST_SETTINGS_SCHEMA["properties"],
            ...EMAIL_SETTINGS_SCHEMA["properties"],
        }),
    );

    const [showTestDigestModal, setShowTestDigestModal] = useState<boolean>(false);
    const [values, setValues] = useState<any>(null);

    useEffect(() => {
        if (settings?.data) {
            setValues(settings.data);
        }
    }, [settings]);

    const isDirty = useMemo(() => {
        if (settings?.data && values) {
            /**
             * Normalize values to match the stored config data types
             */
            const normalized = {
                ...values,
                "emailDigest.postCount": parseInt(values["emailDigest.postCount"]),
                "emailDigest.dayOfWeek": parseInt(values["emailDigest.dayOfWeek"]),
                "emailDigest.footer":
                    typeof values["emailDigest.footer"] !== "string"
                        ? JSON.stringify(values["emailDigest.footer"])
                        : values["emailDigest.footer"],
                "emailDigest.introduction":
                    typeof values["emailDigest.introduction"] !== "string"
                        ? JSON.stringify(values["emailDigest.introduction"])
                        : values["emailDigest.introduction"],
            };

            return !isEqual(normalized, settings.data);
        }
        return false;
    }, [values, settings]);

    useRouteChangePrompt(
        t(
            "You are leaving the Email Settings page without saving your changes. Make sure your updates are saved before exiting.",
        ),
        !isDirty,
    );

    const emailSettings = useMemo(() => {
        const refinedEmailSettings = {
            // initial values
            ...Object.keys(EMAIL_SETTINGS_SCHEMA.properties).reduce((acc, currentKey) => {
                const value = EMAIL_SETTINGS_SCHEMA.properties[currentKey];
                return {
                    ...acc,
                    [currentKey]: value.type === "boolean" ? false : value.type === "number" ? 1 : "",
                };
            }, {}),
            // final values with actual data
            ...(settings.data &&
                Object.fromEntries(
                    Object.keys(EMAIL_SETTINGS_SCHEMA.properties).map((key) => {
                        if (key === "emailNotifications.disabled") {
                            return [key, !settings.data[key]];
                        } else if (key === "emailStyles.format") {
                            return [key, settings.data[key] === "html" ? true : false];
                        }
                        return [key, settings.data[key] ?? ""];
                    }),
                )),
        };

        return refinedEmailSettings;
    }, [EMAIL_SETTINGS_SCHEMA.properties, settings.data]);

    // Validation & save
    const schemaFormRef = useRef<IJsonSchemaFormHandle | null>(null);
    const [fieldErrors, setFieldErrors] = useState<IFieldError[]>([]);
    const validateForm = () => {
        const result = schemaFormRef?.current?.validate();
        setFieldErrors(validationErrorsToFieldErrors(result?.errors));
        return result?.valid;
    };

    const handleFormSubmit = async () => {
        if (validateForm()) {
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
            } catch (e) {
                if (e.errors) {
                    setFieldErrors(e.errors);
                }
            }
        }
    };

    // if errors are present, scroll to the first error
    useEffect(() => {
        const errorElement = document.querySelector('[id^="errorMessages"]');
        if (errorElement) {
            errorElement?.scrollIntoView({ behavior: "smooth" });
        }
    }, [fieldErrors]);

    const normalizedValues = {
        ...values,
        // Disallow line breaks in subject & title field
        "emailDigest.title": (values?.["emailDigest.title"] ?? "").replace(/(\r\n|\n|\r)/gm, ""),
    };

    const scrollRefs = useRef<HTMLDivElement[]>([]);

    const addToRefs = useCallback((el: HTMLDivElement | null, index: number) => {
        if (!el || scrollRefs.current.includes(el)) return;
        scrollRefs.current.splice(index, 0, el);
    }, []);

    const scrollToRef = (index: any) => {
        scrollRefs.current?.[index]?.scrollIntoView({ behavior: "smooth" });
    };

    return (
        <MemoryRouter>
            <form
                role="form"
                onSubmit={(e) => {
                    e.preventDefault();
                    handleFormSubmit();
                }}
                className={classes.root}
                noValidate
            >
                <DashboardHeaderBlock
                    title={t("Digest Settings")}
                    actionButtons={
                        <Button
                            buttonType={ButtonTypes.DASHBOARD_PRIMARY}
                            disabled={isPatchLoading || settings.status !== LoadStatus.SUCCESS}
                            submit
                        >
                            {t("Save")}
                        </Button>
                    }
                />
                <DashboardFormList>
                    {values &&
                        sections.map((section, index) => (
                            <div
                                key={index}
                                ref={(ele) => addToRefs(ele, index)}
                                className={cx(classes.section, { [classes.contentSection]: section === "Content" })}
                            >
                                <DashboardFormSubheading hasBackground>
                                    {t(section)}
                                    {section === "General" && (
                                        <>
                                            <DropDown name={t("Email Digest Options")} flyoutType={FlyoutType.LIST}>
                                                <DropDownItemButton
                                                    name={t("Send Test Digest")}
                                                    onClick={() => {
                                                        setShowTestDigestModal(true);
                                                    }}
                                                />
                                            </DropDown>
                                        </>
                                    )}
                                </DashboardFormSubheading>
                                <JsonSchemaForm
                                    disabled={settings.status !== LoadStatus.SUCCESS}
                                    fieldErrors={error?.errors ?? fieldErrors ?? {}}
                                    schema={
                                        section === "General"
                                            ? getDigestSettingsSchemas().emailDigestGeneralSchema
                                            : getDigestSettingsSchemas().emailDigestContentSchema
                                    }
                                    instance={normalizedValues}
                                    FormControlGroup={DashboardFormControlGroup}
                                    FormControl={DashboardFormControl}
                                    FormGroupWrapper={(props) => {
                                        return (
                                            <li
                                                className={cx("form-group", "meta-group-header", {
                                                    [classes.hidden]: !props.rootInstance?.["emailDigest.enabled"],
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
                                    onChange={setValues}
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
                    <Translate source="Styles and general visual appearance of all emails, including email digest, are set site-wide on Email Settings page." />
                </p>
                <p>
                    <Translate
                        source="<0>Read More</0>"
                        c0={(text) => (
                            <SmartLink to="https://success.vanillaforums.com/kb/articles/1479-email-digest">
                                {text}
                            </SmartLink>
                        )}
                    />
                </p>
            </DashboardHelpAsset>
            {showTestDigestModal && (
                <TestDigestModal settings={emailSettings} onCancel={() => setShowTestDigestModal(false)} />
            )}
        </MemoryRouter>
    );
}
