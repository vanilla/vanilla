/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { NewQuickLinksLabItem } from "@dashboard/labs/NewQuickLinksLabItem";
import { NewSearchPageLabItem } from "@dashboard/labs/NewSearchPageLabItem";
import { UserCardsLabItem } from "@dashboard/labs/UserCardsLabItem";
import { NewPostMenuLabItem } from "@dashboard/labs/NewPostMenuLabItem";
import AddonList from "@library/addons/AddonList";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n";
import React from "react";
import { MemoryRouter } from "react-router";
import { CustomLayoutLabItem } from "@dashboard/labs/CustomLayoutLabItem";
import Translate from "@library/content/Translate";
import { NewEmbedSystemLabItem } from "@dashboard/labs/NewEmbedSystemLabItem";

export function VanillaLabsPage() {
    return (
        <MemoryRouter>
            <DashboardHeaderBlock title={t("Vanilla Labs")} />
            <div className="padded">{t("Enable and test out the latest Vanilla features.")}</div>
            <div className={dashboardClasses().extendRow}>
                <AddonList>
                    {VanillaLabsPage.extraLabComponents.map((ComponentName, index) => {
                        return <ComponentName key={index} />;
                    })}
                    <CustomLayoutLabItem />
                    <UserCardsLabItem />
                    <NewSearchPageLabItem />
                    <NewQuickLinksLabItem />
                    <NewPostMenuLabItem />
                    <NewEmbedSystemLabItem />
                </AddonList>
            </div>
            <DashboardHelpAsset>
                <h3>{t("Welcome to Labs!")}</h3>
                <p>{t("This is where you can enable and test out new Vanilla features, pages & components.")}</p>
                <h3>{t("Need more help?")}</h3>
                <Translate
                    source="Check out our <0>documentation</0>, or ask our <1>community of experts</1>."
                    c0={(content) => <SmartLink to="https://success.vanillaforums.com/kb">{content}</SmartLink>}
                    c1={(content) => <SmartLink to="https://success.vanillaforums.com/">{content}</SmartLink>}
                />
            </DashboardHelpAsset>
        </MemoryRouter>
    );
}

/** Hold the extra lab components before rendering. */
VanillaLabsPage.extraLabComponents = [] as React.ComponentType[];

/**
 * Register an extra lab component to be rendered before lab items.
 * Mainly for items coming from plugins/addons e.g. new analytics.
 *
 * @param component The component class to be render.
 */
VanillaLabsPage.registerBeforeLabItems = (component: React.ComponentType) => {
    VanillaLabsPage.extraLabComponents.push(component);
};
