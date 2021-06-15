/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { Tabs } from "@library/sectioning/Tabs";

import { t } from "@vanilla/i18n";
import React, { useMemo, useState } from "react";
import { MemoryRouter } from "react-router";
import { MachineTranslationSettings } from "@dashboard/languages/MachineTranslationSettings";
import { MachineTranslationConfigurationModal } from "@dashboard/languages/MachineTranslationConfigurationModal";
import { IAddon, ITranslationService } from "@dashboard/languages/LanguageSettingsTypes";
import {
    useDefaultLocales,
    useLanguageConfig,
    useLocales,
    useConfigPatcher,
    useConfigsByKeys,
} from "@library/config/configHooks";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import SmartLink from "@library/routing/links/SmartLink";
import { LocaleSettings } from "@dashboard/languages/LocaleSettings";
import { css } from "@emotion/css";
import { LocaleConfigurationModal } from "@dashboard/languages/LocaleConfigurationModal";

export function LanguageSettingsPage() {
    const [editLocale, setEditLocale] = useState<IAddon | null>(null);
    const [configService, setConfigService] = useState<ITranslationService | null>(null);
    const { setTranslationService, translationServices, hasMachineTranslation } = useLanguageConfig(
        configService?.type,
    );
    const { defaultLocale, setDefaultLocale } = useDefaultLocales();
    const { setLocale, allLocales, localeOptions } = useLocales();
    const translationConfig = useConfigsByKeys(["machineTranslation.enabled"]);
    const isTranslationEnabled = translationConfig.data && translationConfig.data["machineTranslation.enabled"];
    const { patchConfig } = useConfigPatcher();

    const handleConfigSet = (newConfig: any) => {
        if (Object.keys(newConfig).length > 0) {
            setTranslationService(newConfig);
            setConfigService(null);
        }
    };

    const handleLocaleSet = (localeToPatch: { isEnabled: boolean; addonID: string }) => {
        setLocale(localeToPatch);
    };

    const headerBorderOverride = css({
        borderBottom: "none",
    });

    const localeContent = {
        label: t("Localization"),
        contents: (
            <LocaleSettings
                localeOptions={localeOptions}
                defaultLocale={defaultLocale}
                setDefaultLocale={setDefaultLocale}
                localeList={allLocales}
                setLocaleEnabled={handleLocaleSet}
                onEdit={(locale) => setEditLocale(locale)}
                canConfigure={hasMachineTranslation && isTranslationEnabled}
            />
        ),
    };
    const machineTranslationContent = useMemo(() => {
        const setTranslationEnabled = (isEnabled: boolean) => {
            patchConfig({ "machineTranslation.enabled": isEnabled });
        };
        if (hasMachineTranslation) {
            return {
                label: t("Machine Translation"),
                contents: (
                    <MachineTranslationSettings
                        isEnabled={isTranslationEnabled}
                        setEnabled={setTranslationEnabled}
                        services={translationServices ? translationServices : []}
                        configureService={setConfigService}
                    />
                ),
            };
        }
        return {
            label: t("Machine Translation"),
            disabled: true,
            contents: null,
            info: t(
                "Machine Translation is a Knowledge Base feature. If you want to learn more about Knowledge Base, contact your CSM.",
            ),
        };
    }, [hasMachineTranslation, isTranslationEnabled, patchConfig, translationServices]);

    return (
        <MemoryRouter>
            <DashboardHeaderBlock title={t("Language Settings")} className={headerBorderOverride} />
            <section>
                <Tabs
                    tabListClasses={dashboardClasses().extendRow}
                    defaultTabIndex={0}
                    data={[localeContent, machineTranslationContent]}
                />
                <LocaleConfigurationModal
                    isVisible={!!editLocale}
                    onExit={() => setEditLocale(null)}
                    locale={editLocale}
                />
                <MachineTranslationConfigurationModal
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
