/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useEffect, useRef, useCallback } from "react";
import { useRouteChangePrompt } from "@vanilla/react-utils";
import { JsonSchemaForm, JSONSchemaType } from "@vanilla/json-schema-forms";
import { getMeta } from "@library/utility/appUtils";
import { useLastValue } from "@vanilla/react-utils";
import { LoadStatus } from "@library/@types/api/core";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import TestEmailModal from "@dashboard/emailSettings/components/TestEmailModal";
import EmailPreviewModal from "@dashboard/emailSettings/components/EmailPreviewModal";
import TestDigestModal from "@dashboard/emailSettings/components/TestDigestModal";
import DigestSchedule from "@dashboard/emailSettings/components/DigestSchedule";
import {
    IEmailStyleSettings,
    IEmailOutgoingSettings,
    IEmailNotificationSettings,
    IEmailDigestSettings,
    IEmailSettings,
    IEmailConfigs,
} from "@dashboard/emailSettings/types";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@vanilla/i18n";
import { MemoryRouter } from "react-router";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";

const EMAIL_STYLES_SECTION = "Email Styles";
const OUTGOING_EMAILS_SECTION = "Outgoing Emails";
const EMAIL_NOTIFICATIONS_SECTION = "Email Notifications";
const EMAIL_DIGEST_SECTION = "Email Digest";

export function EmailSettings() {
    const EMAIL_STYLES_SCHEMA: JSONSchemaType<IEmailStyleSettings> = {
        type: "object",
        properties: {
            "emailStyles.format": {
                type: "boolean",
                "x-control": {
                    label: t("Enable HTML emails"),
                    description: t("Spruce up your emails by adding a logo and customizing the colors."),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                },
            },
            "emailStyles.image": {
                type: "string",
                nullable: true,
                maxLength: 500,
                "x-control": {
                    label: t("Email Logo"),
                    description: t(
                        "Large images will be scaled down to a max width of 400px and a max height of 300px.",
                    ),
                    inputType: "upload",
                    conditions: [{ field: "emailStyles.format", type: "boolean", const: true }],
                },
            },
            "emailStyles.textColor": {
                type: "string",
                nullable: true,
                maxLength: 9,
                "x-control": {
                    label: t("Text Color"),
                    inputType: "color",
                    conditions: [{ field: "emailStyles.format", type: "boolean", const: true }],
                },
            },
            "emailStyles.backgroundColor": {
                type: "string",
                nullable: true,
                maxLength: 9,
                "x-control": {
                    label: t("Background Color"),
                    inputType: "color",
                    conditions: [{ field: "emailStyles.format", type: "boolean", const: true }],
                },
            },
            "emailStyles.containerBackgroundColor": {
                type: "string",
                nullable: true,
                maxLength: 9,
                "x-control": {
                    label: t("Page Color"),
                    inputType: "color",
                    conditions: [{ field: "emailStyles.format", type: "boolean", const: true }],
                },
            },
            "emailStyles.buttonTextColor": {
                type: "string",
                nullable: true,
                maxLength: 9,
                "x-control": {
                    label: t("Button Text Color"),
                    inputType: "color",
                    conditions: [{ field: "emailStyles.format", type: "boolean", const: true }],
                },
            },
            "emailStyles.buttonBackgroundColor": {
                type: "string",
                nullable: true,
                maxLength: 9,
                "x-control": {
                    label: t("Button Background Color"),
                    inputType: "color",
                    conditions: [{ field: "emailStyles.format", type: "boolean", const: true }],
                },
            },
        },
    };

    const OUTGOING_EMAILS_SCHEMA: JSONSchemaType<IEmailOutgoingSettings> = {
        type: "object",
        properties: {
            "outgoingEmails.supportName": {
                type: "string",
                nullable: true,
                minLength: 1,
                "x-control": {
                    label: t("From Name"),
                    description: t("Email sent from the application will be addressed from this name"),
                    inputType: "textBox",
                    default: "Default From Name",
                },
            },
            "outgoingEmails.supportAddress": {
                type: "string",
                nullable: true,
                minLength: 1,
                "x-control": {
                    label: t("From Email Address"),
                    description: t("Email sent from the application will be addressed from this email address"),
                    inputType: "textBox",
                    default: "Default Email Address",
                },
            },
            "outgoingEmails.footer": {
                type: "string",
                nullable: true,
                maxLength: 500,
                "x-control": {
                    label: t("Email Footer"),
                    description: t(
                        "This may be used to include content such as organization name and address in all outgoing emails.",
                    ),
                    inputType: "richeditor",
                },
            },
        },
        required: ["outgoingEmails.supportName", "outgoingEmails.supportAddress"],
    };

    const EMAIL_NOTIFICATIONS_SCHEMA: JSONSchemaType<IEmailNotificationSettings> = {
        type: "object",
        properties: {
            "emailNotifications.disabled": {
                type: "boolean",
                "x-control": {
                    label: t("Enable sending notification emails"),
                    description: t("When enabled, users may choose to receive notifications from the community"),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                },
            },
            "emailNotifications.fullPost": {
                type: "boolean",
                "x-control": {
                    label: t("Include full post in email notifications"),
                    description: t(
                        "If enabled, the full content of posts will be sent in email notifications to users.",
                    ),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                    conditions: [{ field: "emailNotifications.disabled", type: "boolean", const: true }],
                },
            },
        },
    };

    const EMAIL_DIGEST_SCHEMA: JSONSchemaType<IEmailDigestSettings> = {
        type: "object",
        properties: {
            "emailDigest.enabled": {
                type: "boolean",
                "x-control": {
                    label: t("Use weekly community email digest"),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                },
            },
            "emailDigest.imageEnabled": {
                type: "boolean",
                "x-control": {
                    label: t("Include featured images with posts in email digest"),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                    conditions: [{ field: "emailDigest.enabled", type: "boolean", const: true }],
                },
            },
            "emailDigest.dayOfWeek": {
                type: "number",
                "x-control": {
                    label: t("Delivery Date"),
                    description: `
                    ${t(
                        "Email digests will be sent on the selected day every week. Changes to the set day will take affect the following week.",
                    )}
                    <br/>
                    <a href="https://success.vanillaforums.com/kb/articles/1479-email-digest" target="_blank">
                        ${t("More information")}
                    </a>
                `,
                    inputType: "dropDown",
                    choices: {
                        staticOptions: {
                            1: t("weekday.long.1", "Monday"),
                            2: t("weekday.long.2", "Tuesday"),
                            3: t("weekday.long.3", "Wednesday"),
                            4: t("weekday.long.4", "Thursday"),
                            5: t("weekday.long.5", "Friday"),
                            6: t("weekday.long.6", "Saturday"),
                            7: t("weekday.long.7", "Sunday"),
                        },
                    },
                    conditions: [{ field: "emailDigest.enabled", type: "boolean", const: true }],
                },
            },
        },
        required: ["emailDigest.dayOfWeek"],
    };

    const isDigestEnabled = getMeta("featureFlags.Digest.Enabled") === true;

    const EMAIL_SETTINGS: JSONSchemaType<IEmailSettings> = {
        type: "object",
        properties: {
            ...EMAIL_STYLES_SCHEMA.properties,
            ...OUTGOING_EMAILS_SCHEMA.properties,
            ...EMAIL_NOTIFICATIONS_SCHEMA.properties,
            ...(isDigestEnabled && EMAIL_DIGEST_SCHEMA.properties),
        },
    };

    const settings = useConfigsByKeys(Object.keys(EMAIL_SETTINGS["properties"]));
    const isLoaded = [LoadStatus.SUCCESS, LoadStatus.ERROR].includes(settings.status);
    const wasLoaded = useLastValue(isLoaded);
    const { isLoading: isPatchLoading, patchConfig, error } = useConfigPatcher();
    const [showTestEmailModal, setShowTestEmailModal] = useState<boolean>(false);
    const [showPreviewEmailModal, setShowPreviewEmailModal] = useState<boolean>(false);
    const [showTestDigestModal, setShowTestDigestModal] = useState<boolean>(false);
    const [value, setValue] = useState<IEmailSettings | {}>(
        Object.keys(EMAIL_SETTINGS.properties).reduce((acc, currentKey) => {
            const value = EMAIL_SETTINGS.properties[currentKey];
            return {
                ...acc,
                [currentKey]: value.type === "boolean" ? false : value.type === "number" ? 1 : "",
            };
        }, {}),
    );

    const [settingsLoaded, setSettingsLoaded] = useState<boolean>(false);

    useEffect(() => {
        // Initialize the values we just loaded.
        if (!wasLoaded && isLoaded && settings.data) {
            setValue((existing) => ({
                ...existing,
                ...Object.fromEntries(
                    Object.keys(settings.data).map((key) => {
                        if (key === "emailNotifications.disabled") {
                            return [key, !settings.data[key]];
                        } else if (key === "emailStyles.format") {
                            return [key, settings.data[key] === "html" ? true : false];
                        }
                        return [key, settings.data[key]];
                    }),
                ),
            }));
            setSettingsLoaded(true);
        }
    }, [wasLoaded, isLoaded, settings.data]);

    useEffect(() => {
        if (!value["emailNotifications.disabled"]) {
            setValue((existing) => ({
                ...existing,
                "emailDigest.enabled": false,
            }));
        }
    }, [value["emailNotifications.disabled"]]);

    const scrollRefs = useRef<HTMLDivElement[]>([]);

    const addToRefs = useCallback((el: HTMLDivElement | null, index: number) => {
        if (!el || scrollRefs.current.includes(el)) return;
        scrollRefs.current.splice(index, 0, el);
    }, []);

    const scrollToRef = (index) => {
        scrollRefs.current?.[index]?.scrollIntoView({ behavior: "smooth" });
    };

    let sections = [EMAIL_STYLES_SECTION, OUTGOING_EMAILS_SECTION, EMAIL_NOTIFICATIONS_SECTION];

    if (isDigestEnabled && value["emailNotifications.disabled"]) {
        sections.push(EMAIL_DIGEST_SECTION);
    }

    const [isFormEdited, setIsFormEdited] = useState<boolean>(false);
    const [disabledRouteChangePrompt, setDisableRouteChangePrompt] = useState<boolean>(true);
    useRouteChangePrompt(
        t(
            "You are leaving the Email Settings page without saving your changes. Make sure your updates are saved before exiting.",
        ),
        disabledRouteChangePrompt,
    );

    const handleSubmit = () => {
        patchConfig(
            Object.fromEntries(
                Object.keys(value).map((key) => {
                    if (key === "emailNotifications.disabled") {
                        return [key, !value[key]];
                    } else if (key === "emailStyles.format") {
                        return [key, value[key] ? "html" : "text"];
                    } else if (key === "outgoingEmails.footer") {
                        return [key, JSON.stringify(value[key])];
                    }
                    return [key, value[key]];
                }),
            ) as IEmailConfigs,
        );
        setDisableRouteChangePrompt(true);
    };

    return (
        <MemoryRouter>
            <DashboardHeaderBlock
                title={t("Email Settings")}
                actionButtons={
                    <Button
                        buttonType={ButtonTypes.DASHBOARD_PRIMARY}
                        disabled={isPatchLoading || !isLoaded}
                        onClick={() => handleSubmit()}
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
                                schema={
                                    section === EMAIL_STYLES_SECTION
                                        ? EMAIL_STYLES_SCHEMA
                                        : section === OUTGOING_EMAILS_SECTION
                                        ? OUTGOING_EMAILS_SCHEMA
                                        : section === EMAIL_NOTIFICATIONS_SECTION
                                        ? EMAIL_NOTIFICATIONS_SCHEMA
                                        : EMAIL_DIGEST_SCHEMA
                                }
                                instance={value}
                                FormControlGroup={DashboardFormControlGroup}
                                FormControl={DashboardFormControl}
                                onChange={(value) => {
                                    setValue(value);
                                    if (isFormEdited) {
                                        setDisableRouteChangePrompt(false);
                                    }
                                    setIsFormEdited(true);
                                }}
                            />

                            {section === EMAIL_DIGEST_SECTION && value["emailDigest.enabled"] && (
                                <DigestSchedule dayOfWeek={value["emailDigest.dayOfWeek"]} />
                            )}
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
                <EmailPreviewModal settings={value} onCancel={() => setShowPreviewEmailModal(false)} />
            )}
            {showTestEmailModal && <TestEmailModal settings={value} onCancel={() => setShowTestEmailModal(false)} />}
            {showTestDigestModal && <TestDigestModal settings={value} onCancel={() => setShowTestDigestModal(false)} />}
        </MemoryRouter>
    );
}
