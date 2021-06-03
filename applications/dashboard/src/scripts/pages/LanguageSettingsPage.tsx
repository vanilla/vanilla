/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { Tabs } from "@library/sectioning/Tabs";

import { t } from "@vanilla/i18n";
import React, { useEffect, useState } from "react";
import { MemoryRouter } from "react-router";
import { MachineTranslationSettings } from "@dashboard/languages/MachineTranslationSettings";
import { ConfigurationModal } from "@dashboard/languages/ConfigurationModal";
import { useConfigPatcher, useConfigsByKeys, useLanguageConfig } from "@library/config/configHooks";
import { ITranslationService } from "@dashboard/languages/LanguageSettingsTypes";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import SmartLink from "@library/routing/links/SmartLink";

export function LanguageSettingsPage() {
    const [configService, setConfigService] = useState<ITranslationService | null>(null);
    const { setTranslationService, translationServices } = useLanguageConfig(configService?.type);
    const translationConfig = useConfigsByKeys(["machineTranslation.enabled"]);
    const isTranslationEnabled = translationConfig.data && translationConfig.data["machineTranslation.enabled"];
    const { patchConfig } = useConfigPatcher();
    const setTranslationEnabled = (isEnabled: boolean) => {
        patchConfig({ "machineTranslation.enabled": isEnabled });
    };
    const handleConfigSet = (newConfig: any) => {
        if (Object.keys(newConfig).length > 0) {
            setTranslationService(newConfig);
            setConfigService(null);
        }
    };

    return (
        <MemoryRouter>
            <DashboardHeaderBlock title={t("Language Settings")} />
            <div className="padded">
                {t(
                    "Enable languages to configure per-language subcommunities and Knowledge Bases, and to configure machine translations tools for Knowledge Base articles.",
                )}
            </div>
            <section>
                <Tabs
                    tabListClasses={dashboardClasses().extendRow}
                    defaultTabIndex={1}
                    data={[
                        {
                            label: t("Localization"),
                            disabled: true,
                            contents: "Localization content will go here",
                        },
                        {
                            label: t("Machine Translation"),
                            contents: (
                                <MachineTranslationSettings
                                    isEnabled={isTranslationEnabled}
                                    setEnabled={setTranslationEnabled}
                                    services={translationServices ? translationServices : []}
                                    configureService={setConfigService}
                                />
                            ),
                        },
                    ]}
                />
                <ConfigurationModal
                    isVisible={!!configService}
                    onExit={() => setConfigService(null)}
                    service={configService}
                    setConfiguration={handleConfigSet}
                />
            </section>
            <DashboardHelpAsset>
                <h3>{t("About Language Settings")}</h3>
                <p>
                    {t(
                        "Locales and Machine Translations allow you to support other languages on your site. Enable and disable locales you want to make available here.",
                    )}
                </p>
                <h3>{t("Need more help?")}</h3>
                <p>
                    <SmartLink to="http://docs.vanillaforums.com/developers/locales/">
                        {t("Internationalization & Localization")}
                    </SmartLink>
                </p>
            </DashboardHelpAsset>
        </MemoryRouter>
    );
}
