/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardMediaAddonListItem } from "@dashboard/forms/DashboardMediaAddonListItem";
import { DashboardSelect } from "@dashboard/forms/DashboardSelect";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { languageSettingsStyles } from "@dashboard/languages/LanguageSettings.styles";
import { IAddon } from "@dashboard/languages/LanguageSettingsTypes";
import { cx } from "@emotion/css";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import React, { useState } from "react";

export interface ILocaleSettingsProps {
    localeList: IAddon[];
    setLocaleEnabled({ isEnabled, addonID }: { isEnabled: boolean; addonID: string }): void;
    defaultLocale: IComboBoxOption | undefined;
    localeOptions: IComboBoxOption[] | undefined;
    setDefaultLocale(option: IComboBoxOption): void;
    onEdit?(locale: IAddon): void;
    canConfigure: boolean;
}

export const LocaleSettings = (props: ILocaleSettingsProps) => {
    const { localeList, setLocaleEnabled, defaultLocale, localeOptions, setDefaultLocale, canConfigure, onEdit } =
        props;
    const classes = dashboardClasses();
    const languageClasses = languageSettingsStyles();

    return (
        <>
            <div className={languageClasses.subHeader}>
                {t(
                    "Enable languages to configure per-language subcommunities and Knowledge Bases, and to configure machine translations tools for Knowledge Base articles.",
                )}
            </div>
            <form>
                <DashboardFormGroup label={t("Default Language")}>
                    <DashboardSelect
                        options={localeOptions}
                        value={defaultLocale}
                        onChange={setDefaultLocale}
                        isClearable={false}
                    />
                </DashboardFormGroup>
                {localeList.length > 0 ? (
                    <DashboardFormList>
                        {localeList.map((locale) => {
                            const common = {
                                iconUrl: locale.iconUrl,
                                title: locale.name,
                                description: locale.description,
                                isEnabled: locale.enabled,
                                onChange: (bool: boolean) =>
                                    setLocaleEnabled({ isEnabled: bool, addonID: locale.addonID }),
                                disabled: defaultLocale?.value === locale?.attributes?.locale,
                                disabledNote: t(
                                    "This locale cannot be disabled because it is currently the default language.",
                                ),
                            };
                            const action =
                                canConfigure && locale.enabled && onEdit
                                    ? {
                                          action: () => onEdit(locale),
                                          actionLabel: t("Configure"),
                                          actionIcon: <Icon icon={"dashboard-edit"} />,
                                      }
                                    : {};
                            return <DashboardMediaAddonListItem key={locale.addonID} {...common} {...action} />;
                        })}
                    </DashboardFormList>
                ) : (
                    <section className={cx(languageClasses.addonLoaderLayout, classes.extendBottomBorder)}>
                        <LoadingRectangle width={84} height={84} />
                        <div>
                            <LoadingRectangle width="20%" height={16} />
                            <LoadingRectangle width="40%" height={14} />
                            <LoadingRectangle width="35%" height={14} />
                        </div>
                        <LoadingRectangle width={24} height={24} />
                        <LoadingRectangle width={72} height={30} />
                    </section>
                )}
            </form>
        </>
    );
};
