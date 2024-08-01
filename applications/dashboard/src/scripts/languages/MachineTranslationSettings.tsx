/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardFormListItem } from "@dashboard/forms/DashboardFormListItem";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { languageSettingsStyles } from "@dashboard/languages/LanguageSettings.styles";
import { ITranslationService } from "@dashboard/languages/LanguageSettingsTypes";
import { cx } from "@emotion/css";
import Heading from "@library/layout/Heading";
import Loader from "@library/loaders/Loader";
import { LoadingRectangle, LoadingSpacer } from "@library/loaders/LoadingRectangle";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import React, { useMemo } from "react";
export interface IMachineTranslationProps {
    services: ITranslationService[];
    configureService(service: ITranslationService): void;
    isEnabled: boolean;
    setEnabled(bool: boolean): void;
}

export const MachineTranslationSettings = (props: IMachineTranslationProps) => {
    const { services, configureService, isEnabled, setEnabled } = props;
    const classes = dashboardClasses();
    const languageClasses = languageSettingsStyles();

    const isAnyConfigured = useMemo(() => {
        return services.map((service) => service.isConfigured).some((bool) => bool);
    }, [services]);

    return (
        <>
            <form>
                <DashboardFormGroup
                    labelType={DashboardLabelType.WIDE}
                    label={t("Enable Machine Translation")}
                    description={t(
                        "Enable Machine Translation and configure your translation service providers to translate Knowledge Base articles.",
                    )}
                    afterDescription={
                        !isAnyConfigured &&
                        isEnabled && (
                            <span className={languageClasses.warning}>
                                {t("You must configure at least one service provider to use machine translations.")}
                            </span>
                        )
                    }
                >
                    <DashboardToggle checked={!!isEnabled} onChange={setEnabled} />
                </DashboardFormGroup>
                <DashboardFormList isBlurred={!isEnabled}>
                    <Heading depth={3}>{t("Translation Service Providers")}</Heading>
                    {services.length > 0 ? (
                        <DashboardFormList>
                            {services.map((service) => (
                                <DashboardFormListItem
                                    key={service.type}
                                    title={service.name}
                                    status={service.isConfigured ? t("Configured") : t("Not Configured")}
                                    action={() => configureService(service)}
                                    actionIcon={<Icon icon={"dashboard-edit"} />}
                                    actionLabel={`${t("Edit")} ${service.name}`}
                                    className={classes.extendBottomBorder}
                                />
                            ))}
                        </DashboardFormList>
                    ) : (
                        <section className={cx(languageClasses.loaderLayout, classes.extendBottomBorder)}>
                            <LoadingRectangle width="25%" height={16} />
                            <LoadingRectangle width="10%" height={16} />
                            <LoadingRectangle width="2%" height={16} />
                        </section>
                    )}
                </DashboardFormList>
            </form>
        </>
    );
};
