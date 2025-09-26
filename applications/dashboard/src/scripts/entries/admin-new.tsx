/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { getAppearanceRoutes } from "@dashboard/appearance/routes/appearanceRoutes";
import { AppContext, registerContextProvider } from "@library/AppContext";
import { supportsFrames } from "@library/embeddedContent/IFrameEmbed";
import { ErrorPage } from "@library/errorPages/ErrorComponent";
import SiteNavProvider from "@library/navigation/SiteNavContext";
import { registerReducer } from "@library/redux/reducerRegistry";
import { Router } from "@library/Router";
import { TextEditorContextProvider } from "@library/textEditor/MonacoEditor";
import { themeSettingsReducer } from "@library/theming/themeSettingsReducer";
import { addComponent, addPageComponent } from "@library/utility/componentRegistry";
import { applySharedPortalContext } from "@vanilla/react-utils";
import "../../../design/admin-new.css";
import { layoutSettingsSlice } from "@dashboard/layout/layoutSettings/LayoutSettings.slice";
import { t } from "@vanilla/i18n";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { getVanillaStaffRoutes } from "@dashboard/developer/getVanillaStaffRoutes";
import { RouterRegistry } from "@library/Router.registry";
import { userProfilesSlice } from "@dashboard/userProfiles/state/UserProfiles.slice";
import { getAutomationRulesRoutes } from "@dashboard/automationRules/AutomationRules.routes";
import { NavigationLinksModalControl } from "@dashboard/components/navigation/NavigationLinksModalControl";

registerContextProvider(TextEditorContextProvider);
registerReducer(layoutSettingsSlice.name, layoutSettingsSlice.reducer);
registerReducer(userProfilesSlice.name, userProfilesSlice.reducer);

registerReducer("themeSettings", themeSettingsReducer);

addComponent("NavigationLinksModalControl", NavigationLinksModalControl);

applySharedPortalContext((props) => {
    return (
        <AppContext noWrap errorComponent={<ErrorPage />}>
            {props.children}
        </AppContext>
    );
});

RouterRegistry.addRoutes(getAppearanceRoutes());
RouterRegistry.addRoutes(getVanillaStaffRoutes());
RouterRegistry.addRoutes(getAutomationRulesRoutes());
supportsFrames(true);
addPageComponent(AdminApp);

const SETTINGS_PERMISSIONS = ["site.manage", "community.moderate", "posts.moderate", "staff.allow"];
const ANALYTICS_PERMISSIONS = ["data.view", "dashboards.manage"];

function AdminApp() {
    const { hasPermission } = usePermissionsContext();
    if (hasPermission([...SETTINGS_PERMISSIONS, ...ANALYTICS_PERMISSIONS])) {
        return (
            <SiteNavProvider categoryRecordType="panelMenu">
                <Router
                    sectionRoots={["/appearance", "/analytics/v2", "/settings/vanilla-staff", "/dashboard/content"]}
                />
            </SiteNavProvider>
        );
    } else {
        return <ErrorPage error={{ message: t("You don't have permission to view this page.") }} />;
    }
}
