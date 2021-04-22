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
import AddonList from "@library/addons/AddonList";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n";
import React from "react";
import { MemoryRouter } from "react-router";

export function VanillaLabsPage() {
    return (
        <MemoryRouter>
            <DashboardHeaderBlock title={t("Vanilla Labs")} />
            <div className="padded">{t("Enable and test out the latest Vanilla features.")}</div>
            <div className={dashboardClasses().extendRow}>
                <AddonList>
                    <UserCardsLabItem />
                    <NewSearchPageLabItem />
                    <NewQuickLinksLabItem />
                </AddonList>
            </div>
            <DashboardHelpAsset>
                <h3>{t("Welcome to Labs!")}</h3>
                <p>{t("This is where you can enable and test out new Vanilla features, pages & components.")}</p>
                <h3>{t("Need more help?")}</h3>
                <p>
                    <SmartLink to="https://success.vanillaforums.com/kb/articles/384-integrate-foundation-pages-components-into-your-themee">
                        {t("Integrate Foundation Pages & Components Into Your Theme")}
                    </SmartLink>
                </p>
            </DashboardHelpAsset>
        </MemoryRouter>
    );
}
