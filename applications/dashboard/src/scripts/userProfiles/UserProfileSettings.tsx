/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import ProfileFieldForm from "@dashboard/userProfiles/components/ProfileFieldForm";
import { ProfileRedirectForm } from "@dashboard/userProfiles/components/ProfileRedirectForm";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { t } from "@vanilla/i18n";
import React, { useMemo, useState } from "react";
import { MemoryRouter } from "react-router";
import { usePatchProfileField, usePostProfileField } from "@dashboard/userProfiles/state/UserProfiles.hooks";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import { useToast } from "@library/features/toaster/ToastContext";
import { ProfileFieldsList } from "@dashboard/userProfiles/components/ProfileFieldsList";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import { useConfigsByKeys } from "@library/config/configHooks";
import ProfileFieldDelete from "@dashboard/userProfiles/components/ProfileFieldDelete";

const CONFIG_KEY = "labs.customProfileFields";

export function UserProfileSettings() {
    const settings = useConfigsByKeys([CONFIG_KEY]);
    const postProfileField = usePostProfileField();
    const patchProfileField = usePatchProfileField();

    const [profileFieldConfiguration, setProfileFieldConfiguration] = useState<ProfileField | undefined>(undefined);

    function clearProfileFieldConfiguration() {
        setProfileFieldConfiguration(undefined);
    }

    const toast = useToast();

    function handleProfileFieldFormSuccess() {
        toast.addToast({
            autoDismiss: true,
            dismissible: true,
            body: <>{t("The profile field configuration was successfully applied.")}</>,
        });
    }

    const customFieldsEnabled = useMemo(() => {
        return settings?.data?.[CONFIG_KEY] ?? false;
    }, [settings]);

    // Selected field to delete
    const [confirmDelete, setConfirmDelete] = useState<ProfileField | null>(null);

    return (
        <MemoryRouter>
            <DashboardHeaderBlock title={t("User Profile")} />
            <section>
                <ProfileRedirectForm />
                <ErrorBoundary>
                    {customFieldsEnabled && (
                        <ProfileFieldsList
                            onEdit={(fieldToEdit) => setProfileFieldConfiguration(fieldToEdit)}
                            onDelete={setConfirmDelete}
                        />
                    )}
                </ErrorBoundary>
            </section>
            {customFieldsEnabled && (
                <section>
                    <ErrorBoundary>
                        <ProfileFieldForm
                            key={`${profileFieldConfiguration}`}
                            title={
                                profileFieldConfiguration?.apiName ? t("Edit Profile Field") : t("Add Profile Field")
                            }
                            profileFieldConfiguration={profileFieldConfiguration}
                            isVisible={!!profileFieldConfiguration}
                            onSubmit={async (values) => {
                                await (profileFieldConfiguration?.apiName
                                    ? patchProfileField(values)
                                    : postProfileField(values));
                                clearProfileFieldConfiguration();
                                handleProfileFieldFormSuccess();
                            }}
                            onExit={() => {
                                clearProfileFieldConfiguration();
                            }}
                        />
                    </ErrorBoundary>
                    <ProfileFieldDelete field={confirmDelete} close={() => setConfirmDelete(null)} />
                </section>
            )}
            <DashboardHelpAsset>
                <h3>{t("Heads Up!")}</h3>
                <p>
                    {t(
                        "These redirects only apply if the used tag values exist for the destination user. Otherwise the default Vanilla interface is shown.",
                    )}
                </p>
                <p>{t("*Allowed tags: {userID} {name} {ssoID}")}</p>
            </DashboardHelpAsset>
        </MemoryRouter>
    );
}
