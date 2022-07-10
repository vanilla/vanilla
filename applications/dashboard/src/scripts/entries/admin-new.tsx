/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { getAppearanceRoutes } from "@dashboard/appearance/routes/appearanceRoutes";
import { dashboardSectionSlice } from "@dashboard/DashboardSectionSlice";
import { AppContext, registerContextProvider } from "@library/AppContext";
import { supportsFrames } from "@library/embeddedContent/IFrameEmbed";
import { ErrorPage } from "@library/errorPages/ErrorComponent";
import SiteNavProvider from "@library/navigation/SiteNavContext";
import { registerReducer } from "@library/redux/reducerRegistry";
import { Router } from "@library/Router";
import { TextEditorContextProvider } from "@library/textEditor/TextEditor";
import { themeSettingsReducer } from "@library/theming/themeSettingsReducer";
import { addPageComponent } from "@library/utility/componentRegistry";
import { applySharedPortalContext } from "@vanilla/react-utils";
import React from "react";
import "../../../design/admin-new.css";
import { layoutSettingsSlice } from "@dashboard/layout/layoutSettings/LayoutSettings.slice";
import { hasPermission } from "@library/features/users/Permission";
import { t } from "@vanilla/i18n";

registerContextProvider(TextEditorContextProvider);
registerReducer(dashboardSectionSlice.name, dashboardSectionSlice.reducer);
registerReducer(layoutSettingsSlice.name, layoutSettingsSlice.reducer);
registerReducer("themeSettings", themeSettingsReducer);

applySharedPortalContext((props) => {
    return (
        <AppContext noWrap errorComponent={<ErrorPage />}>
            {props.children}
        </AppContext>
    );
});

Router.addRoutes(getAppearanceRoutes());
supportsFrames(true);
addPageComponent(AdminApp);

const SETTINGS_PERMISSIONS = ["settings.manage", "community.moderate"];
const ANALYTICS_PERMISSIONS = ["data.view", "dashboards.manage"];

function AdminApp() {
    if (hasPermission([...SETTINGS_PERMISSIONS, ...ANALYTICS_PERMISSIONS])) {
        return (
            <SiteNavProvider categoryRecordType="panelMenu">
                <Router sectionRoots={["/appearance", "/analytics/v2"]} />
            </SiteNavProvider>
        );
    } else {
        return <ErrorPage error={{ message: t("You don't have permission to view this page.") }} />;
    }
}
