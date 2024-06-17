/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useRef, useCallback } from "react";
import { useRouteChangePrompt } from "@vanilla/react-utils";
import { JsonSchemaForm } from "@vanilla/json-schema-forms";
import { LoadStatus } from "@library/@types/api/core";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import TestEmailModal from "@dashboard/emailSettings/components/TestEmailModal";
import EmailPreviewModal from "@dashboard/emailSettings/components/EmailPreviewModal";
import { IEmailConfigs } from "@dashboard/emailSettings/EmailSettings.types";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@vanilla/i18n";
import { MemoryRouter } from "react-router";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { getEmailSettingsSchemas } from "@dashboard/emailSettings/EmailSettings.utils";
import { getDefaultValuesFromSchema } from "@vanilla/json-schema-forms/src/utils";
import { useFormik } from "formik";

const EMAIL_STYLES_SECTION = "Email Styles";
const OUTGOING_EMAILS_SECTION = "Outgoing Emails";
const EMAIL_NOTIFICATIONS_SECTION = "Email Notifications";

export function EmailSettings() {
    const emailSettingsSchema = getEmailSettingsSchemas().emailSettingsSchema;
    const settings = useConfigsByKeys(Object.keys(emailSettingsSchema["properties"]));
    const isLoaded = [LoadStatus.SUCCESS, LoadStatus.ERROR].includes(settings.status);
    const { isLoading: isPatchLoading, patchConfig, error } = useConfigPatcher();
    const [showTestEmailModal, setShowTestEmailModal] = useState<boolean>(false);
    const [showPreviewEmailModal, setShowPreviewEmailModal] = useState<boolean>(false);

    const defaultValues = getDefaultValuesFromSchema(emailSettingsSchema);

    const isReady = isLoaded && !!settings.data;

    const { values, setValues, dirty, submitForm } = useFormik({
        initialValues: {
            ...defaultValues,
            ...(isReady
                ? Object.fromEntries(
                      Object.keys(settings.data).map((key) => {
                          if (key === "emailNotifications.disabled") {
                              return [key, !settings.data[key]];
                          } else if (key === "emailStyles.format") {
                              return [key, settings.data[key] === "html" ? true : false];
                          } else if (key === "outgoingEmails.footer") {
                              if (typeof settings.data[key] === "string") {
                                  try {
                                      const value = JSON.parse(settings.data[key]);
                                      return [key, value];
                                  } catch (e) {
                                      return defaultValues[key];
                                  }
                              } else {
                                  return [key, settings.data[key]];
                              }
                          }
                          return [key, settings.data[key]];
                      }),
                  )
                : {}),
        },
        enableReinitialize: true,

        onSubmit: async (values, { resetForm }) => {
            const patchValues = {
                ...values,
            };

            if (values["emailNotifications.disabled"] === false) {
                patchValues["emailDigest.enabled"] = false;
            } else if ("emailDigest.enabled" in values) {
                delete patchValues["emailDigest.enabled"];
            }

            await patchConfig(
                Object.fromEntries(
                    Object.keys(patchValues).map((key) => {
                        if (key === "emailNotifications.disabled") {
                            return [key, !patchValues[key]];
                        } else if (key === "emailStyles.format") {
                            return [key, patchValues[key] ? "html" : "text"];
                        } else if (key === "outgoingEmails.footer") {
                            return [key, JSON.stringify(patchValues[key])];
                        }
                        return [key, patchValues[key]];
                    }),
                ) as IEmailConfigs,
            );

            resetForm({ values });
        },
    });

    const scrollRefs = useRef<HTMLDivElement[]>([]);

    const addToRefs = useCallback((el: HTMLDivElement | null, index: number) => {
        if (!el || scrollRefs.current.includes(el)) return;
        scrollRefs.current.splice(index, 0, el);
    }, []);

    const scrollToRef = (index) => {
        scrollRefs.current?.[index]?.scrollIntoView({ behavior: "smooth" });
    };

    let sections = [EMAIL_STYLES_SECTION, OUTGOING_EMAILS_SECTION, EMAIL_NOTIFICATIONS_SECTION];

    useRouteChangePrompt(
        t(
            "You are leaving the Email Settings page without saving your changes. Make sure your updates are saved before exiting.",
        ),
        !dirty,
    );

    return (
        <MemoryRouter>
            <DashboardHeaderBlock
                title={t("Email Settings")}
                actionButtons={
                    <Button
                        buttonType={ButtonTypes.DASHBOARD_PRIMARY}
                        disabled={isPatchLoading || !isLoaded}
                        onClick={async () => await submitForm()}
                    >
                        {t("Save")}
                    </Button>
                }
            />

            <DashboardFormList>
                {isReady &&
                    sections.map((section, index) => (
                        <div key={index} ref={(ele) => addToRefs(ele, index)} style={{ scrollMarginTop: 96 }}>
                            <DashboardFormSubheading hasBackground>
                                {t(section)}
                                {section === EMAIL_STYLES_SECTION && (
                                    <DropDown name={t("Email Styles Options")} flyoutType={FlyoutType.LIST}>
                                        <DropDownItemButton
                                            name={t("Preview")}
                                            onClick={() => {
                                                setShowPreviewEmailModal(true);
                                            }}
                                        />
                                        <DropDownItemButton
                                            name={t("Send a Test Email")}
                                            onClick={() => {
                                                setShowTestEmailModal(true);
                                            }}
                                        />
                                    </DropDown>
                                )}
                            </DashboardFormSubheading>

                            <JsonSchemaForm
                                disabled={!isLoaded}
                                fieldErrors={error?.errors ?? {}}
                                schema={
                                    section === EMAIL_STYLES_SECTION
                                        ? getEmailSettingsSchemas().emailStylesSchema
                                        : section === OUTGOING_EMAILS_SECTION
                                        ? getEmailSettingsSchemas().outgoingEmailSchema
                                        : section === EMAIL_NOTIFICATIONS_SECTION
                                        ? getEmailSettingsSchemas().emailNotificationsSchema
                                        : getEmailSettingsSchemas().emailStylesSchema
                                }
                                instance={values}
                                FormControlGroup={DashboardFormControlGroup}
                                FormControl={DashboardFormControl}
                                onChange={setValues}
                            />
                        </div>
                    ))}
            </DashboardFormList>

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
            </DashboardHelpAsset>

            {showPreviewEmailModal && (
                <EmailPreviewModal settings={values} onCancel={() => setShowPreviewEmailModal(false)} />
            )}
            {showTestEmailModal && <TestEmailModal settings={values} onCancel={() => setShowTestEmailModal(false)} />}
        </MemoryRouter>
    );
}
