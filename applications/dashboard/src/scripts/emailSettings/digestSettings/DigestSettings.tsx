/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { emailSettingsClasses } from "@dashboard/emailSettings/EmailSettings.classes";
import {
    IEmailDigestAdditionalSetting,
    IEmailDigestSettingsConfigValues,
    IEmailDigestSettingsFormValues,
} from "@dashboard/emailSettings/EmailSettings.types";
import {
    getDigestSettingsSchemas,
    getInitialFormValues,
    mapFormValuesToConfigValues,
} from "@dashboard/emailSettings/digestSettings/DigestSettings.utils";
import DigestSchedule from "@dashboard/emailSettings/components/DigestSchedule";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { cx } from "@emotion/css";
import { useConfigMutation, useConfigQuery } from "@library/config/configHooks";
import Translate from "@library/content/Translate";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n";
import { IFieldError, IJsonSchemaFormHandle } from "@vanilla/json-schema-forms";
import { extractSchemaDefaults, mapValidationErrorsToFormikErrors } from "@vanilla/json-schema-forms/src/utils";
import { useRouteChangePrompt } from "@vanilla/react-utils";
import { useFormik } from "formik";
import { useCallback, useEffect, useRef, useState } from "react";
import { MemoryRouter } from "react-router";
import TestDigestModal from "../components/TestDigestModal";

/**
 * This is responsible for registering additional setting (schema properties) as form field (e.g. from groups)
 */

let additionalSettings: IEmailDigestAdditionalSetting = {};
DigestSettings.addAdditionalSetting = (newSetting: IEmailDigestAdditionalSetting) => {
    additionalSettings = { ...additionalSettings, ...newSetting };
};

function DigestSettingsForm(props: {
    initialValues: IEmailDigestSettingsFormValues;
    onSubmit: (values: IEmailDigestSettingsFormValues) => Promise<void>;
}) {
    const schemaFormRef = useRef<IJsonSchemaFormHandle | null>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]>>({});

    const {
        values: nonNormalizedValues,
        setValues,
        submitForm,
        isSubmitting,
        dirty,
    } = useFormik<IEmailDigestSettingsFormValues>({
        initialValues: props.initialValues,
        onSubmit: async (values) => {
            try {
                await props.onSubmit(values);
            } catch (e) {
                if (e.errors) {
                    setFieldErrors(e.errors);
                }
            }
        },
        validate: () => {
            const result = schemaFormRef?.current?.validate();
            const mappedErrors = mapValidationErrorsToFormikErrors(result?.errors ?? []);
            return mappedErrors ?? {};
        },
        enableReinitialize: true,
    });

    const classes = emailSettingsClasses();

    useRouteChangePrompt(
        t(
            "You are leaving the Email Settings page without saving your changes. Make sure your updates are saved before exiting.",
        ),
        !dirty,
    );

    const [showTestDigestModal, setShowTestDigestModal] = useState<boolean>(false);

    // if errors are present, scroll to the first error
    useEffect(() => {
        const errorElement = document.querySelector('[id^="errorMessages"]');
        if (errorElement) {
            errorElement?.scrollIntoView({ behavior: "smooth" });
        }
    }, [fieldErrors]);

    const values = {
        ...nonNormalizedValues,
        // Disallow line breaks in subject & title field
        "emailDigest.title": (nonNormalizedValues?.["emailDigest.title"] ?? "").replace(/(\r\n|\n|\r)/gm, ""),
    };

    let sections = ["General"].concat(values["emailDigest.enabled"] ? ["Delivery", "Content", "Subscription"] : []);

    const scrollRefs = useRef<HTMLDivElement[]>([]);

    const addToRefs = useCallback((el: HTMLDivElement | null, index: number) => {
        if (!el || scrollRefs.current.includes(el)) {
            return;
        }
        scrollRefs.current.splice(index, 1, el);
    }, []);

    const scrollToRef = (index: any) => {
        scrollRefs.current?.[index]?.scrollIntoView({ behavior: "smooth" });
    };

    return (
        <>
            <form
                role="form"
                onSubmit={async (e) => {
                    e.preventDefault();
                    await submitForm();
                }}
                className={classes.root}
            >
                <DashboardHeaderBlock
                    title={t("Digest Settings")}
                    actionButtons={
                        <Button buttonType={ButtonTypes.DASHBOARD_PRIMARY} disabled={isSubmitting} submit>
                            {t("Save")}
                        </Button>
                    }
                />
                <DashboardFormList>
                    {sections.map((section, index) => (
                        <div key={index} ref={(ele) => addToRefs(ele, index)} className={cx(classes.section)}>
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
                            <DashboardSchemaForm
                                disabled={isSubmitting}
                                fieldErrors={fieldErrors}
                                schema={
                                    {
                                        General: getDigestSettingsSchemas().emailDigestGeneralSchema,
                                        Delivery: getDigestSettingsSchemas().emailDigestDeliverySchema,
                                        Content: getDigestSettingsSchemas(additionalSettings).emailDigestContentSchema,
                                        Subscription: getDigestSettingsSchemas().emailDigestSubscriptionSchema,
                                    }[section]!
                                }
                                instance={values}
                                ref={schemaFormRef}
                                onChange={setValues}
                            />

                            {section === "Delivery" &&
                                values["emailDigest.enabled"] &&
                                !!values["emailDigest.dayOfWeek"] && (
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

            {showTestDigestModal && <TestDigestModal onCancel={() => setShowTestDigestModal(false)} />}
        </>
    );
}

export function DigestSettings() {
    const digestSettingsSchema = getDigestSettingsSchemas(additionalSettings).emailDigestSchema;

    let defaultValues = extractSchemaDefaults(digestSettingsSchema) as IEmailDigestSettingsFormValues;

    const settings = useConfigQuery(["emailDigest.*"]);

    const { mutateAsync: patchConfig } = useConfigMutation();

    let initialValues = defaultValues;
    if (settings.data) {
        let configs = settings.data as IEmailDigestSettingsConfigValues;

        initialValues = {
            ...defaultValues,
            ...getInitialFormValues(configs, defaultValues),
        };
    }

    async function handleFormSubmit(values: IEmailDigestSettingsFormValues) {
        const mappedValues = mapFormValuesToConfigValues(values);
        return await patchConfig(mappedValues);
    }

    return (
        <MemoryRouter>
            <DigestSettingsForm initialValues={initialValues} onSubmit={handleFormSubmit} />
        </MemoryRouter>
    );
}
