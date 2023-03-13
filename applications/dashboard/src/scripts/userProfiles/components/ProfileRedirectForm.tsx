/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import { DashboardFormGroupPlaceholder } from "@dashboard/forms/DashboardFormGroupPlaceholder";
import { ProfileRedirectFormClasses } from "@dashboard/userProfiles/components/ProfileRedirectForm.classes";
import { LoadStatus } from "@library/@types/api/core";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { t } from "@vanilla/i18n";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import isEmpty from "lodash/isEmpty";
import isEqual from "lodash/isEqual";
import React, { useEffect, useMemo, useRef, useState } from "react";

const REDIRECT_SCHEMA: JsonSchema = {
    type: "object",
    properties: {
        "redirectURL.profile": {
            type: "string",
            minLength: 1,
            maxLength: 500,
            "x-control": {
                label: `"Profile" redirection URL`,
                description: `Custom URL to redirect the user instead of rendering Vanilla's "Profile" page.`,
                inputType: "textBox",
                type: "url",
            },
        },
        "redirectURL.message": {
            type: "string",
            minLength: 1,
            maxLength: 500,
            "x-control": {
                label: `"New Message" redirection URL`,
                description: `Custom URL to redirect the user instead of rendering Vanilla's "New Message" page.`,
                inputType: "textBox",
                type: "url",
            },
        },
    },
};

export function ProfileRedirectForm() {
    // Form state
    const [formValues, setFormValues] = useState(() =>
        Object.fromEntries(Object.keys(REDIRECT_SCHEMA.properties).map((key) => [key, ""])),
    );

    // Hooks
    const { isLoading: isPatchLoading, patchConfig, error } = useConfigPatcher();
    const toast = useToast();

    // Get the config
    const settings = useConfigsByKeys(Object.keys(REDIRECT_SCHEMA["properties"]));

    // Load state for the setting values
    const isLoaded = useMemo<boolean>(
        () => [LoadStatus.SUCCESS, LoadStatus.ERROR].includes(settings.status),
        [settings],
    );

    const errorToastRef = useRef<string | null>(null);

    // Set the formValues after the config loads
    useEffect(() => {
        if (settings?.data) {
            setFormValues(() =>
                Object.fromEntries(Object.keys(settings.data).map((key) => [key, settings?.data?.[`${key}`]])),
            );
        }
        if (settings?.error && !errorToastRef?.current) {
            errorToastRef.current = toast.addToast({
                dismissible: true,
                body: <>{settings?.error?.message ?? t("Error fetching config")}</>,
            });
        }
    }, [settings, toast]);

    // Patch error
    useEffect(() => {
        if (error && !errorToastRef?.current) {
            errorToastRef.current = toast.addToast({
                dismissible: true,
                body: <>{error?.message ?? t("Error saving config")}</>,
            });
        }
    }, [error, toast]);

    // Track the values to patch
    const touchedSettings = useMemo(() => {
        if (settings.data) {
            return Object.keys(formValues).reduce(
                (delta: { [key: string]: string | number | boolean }, currentKey: string) => {
                    if (!isEqual(formValues[currentKey], settings.data?.[currentKey])) {
                        return { ...delta, [currentKey]: formValues[currentKey] };
                    }
                    return delta;
                },
                {},
            );
        }
        return {};
    }, [settings, formValues]);

    useEffect(() => {
        if (Object.keys(touchedSettings)) {
            errorToastRef.current = null;
        }
    }, [touchedSettings]);

    // Patch it!
    const handleSubmit = () => {
        if (!isEmpty(touchedSettings)) {
            patchConfig(touchedSettings).then(() => {
                toast.addToast({
                    dismissible: true,
                    autoDismiss: true,
                    body: <>{t("Your settings have been saved successfully.")}</>,
                });
            });
        }
    };

    const classes = ProfileRedirectFormClasses();

    return (
        <form data-testid="profile-redirect-form">
            {isLoaded ? (
                <>
                    <JsonSchemaForm
                        schema={REDIRECT_SCHEMA}
                        instance={formValues}
                        FormControlGroup={DashboardFormControlGroup}
                        FormControl={DashboardFormControl}
                        onChange={setFormValues}
                    />
                    <div className={classes.formFooter}>
                        <Button
                            buttonType={ButtonTypes.DASHBOARD_PRIMARY}
                            onClick={() => handleSubmit()}
                            disabled={isPatchLoading || !!errorToastRef?.current}
                        >
                            {isPatchLoading ? <ButtonLoader buttonType={ButtonTypes.DASHBOARD_PRIMARY} /> : t("Save")}
                        </Button>
                    </div>
                </>
            ) : (
                Object.keys(formValues).map((_, index) => <DashboardFormGroupPlaceholder key={index} />)
            )}
        </form>
    );
}
