/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { SettingsAdminLayout } from "@dashboard/components/navigation/SettingsAdminLayout";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import apiv2 from "@library/apiv2";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import Loader from "@library/loaders/Loader";
import Message from "@library/messages/Message";
import { MetaItem } from "@library/metas/Metas";
import { OpenApiViewer } from "@library/openapi/OpenApiViewer";
import { siteUrl } from "@library/utility/appUtils";
import { useQuery } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";

export default function ApiOverviewPage() {
    const specQuery = useQuery({
        queryKey: ["openapi-spec"],
        queryFn: async () => {
            const url = siteUrl("/api/v2/open-api/v3" + window.location.search);
            const result = await apiv2.get(url);
            return result.data;
        },
    });

    return (
        <SettingsAdminLayout
            title={
                <span>
                    {t("Vanilla API v2")}
                    <MetaItem>{siteUrl("/api/v2")}</MetaItem>
                </span>
            }
            content={
                <div className={dashboardClasses().content}>
                    <ErrorBoundary isFixed={false}>
                        {specQuery.isLoading && <Loader />}
                        {specQuery.isError && specQuery.error && (
                            <Message type={"error"} error={specQuery.error as any} />
                        )}
                        {specQuery.data && <OpenApiViewer tryItEnabled={true} spec={specQuery.data} />}
                    </ErrorBoundary>
                </div>
            }
            rightPanel={
                <>
                    <h3>{t("About the API")}</h3>
                    <p>
                        {t(
                            "This page lists the endpoints of your API. Click endpoints for more information. You can make live calls to the API from this page or externally using an access token.",
                        )}
                    </p>
                    <h3>{t("See Also")}</h3>
                    <ul>
                        <li>
                            <a href="https://success.vanillaforums.com/kb/articles/41-authenticating-apiv2-calls-with-personal-access-tokens">
                                {t("Personal Access Tokens")}
                            </a>
                        </li>
                    </ul>
                    <h3>{t("Need More Help?")}</h3>
                    <ul>
                        <li>
                            <a href="https://success.vanillaforums.com/kb/articles/40-api-v2-overview">
                                {t("API Overview")}
                            </a>
                        </li>
                        <li>
                            <a href="https://success.vanillaforums.com/kb/articles/218-authenticating-api-calls">
                                {t("Authentication")}
                            </a>
                        </li>
                        <li>
                            <a href="https://success.vanillaforums.com/kb/articles/44-rate-limits">
                                {t("Rate Limits")}
                            </a>
                        </li>
                    </ul>
                </>
            }
        />
    );
}
