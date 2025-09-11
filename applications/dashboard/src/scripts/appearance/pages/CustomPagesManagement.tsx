/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { CustomPagesUI } from "@dashboard/appearance/customPages/CustomPages";
import { CustomPagesProvider, useCustomPageContext } from "@dashboard/appearance/customPages/CustomPages.context";
import { useCustomPagesQuery } from "@dashboard/appearance/customPages/CustomPages.hooks";
import { customPagesClasses } from "@dashboard/appearance/pages/CustomPagesManagement.classes";
import { AppearanceAdminLayout } from "@dashboard/components/navigation/AppearanceAdminLayout";
import { EmptyState } from "@dashboard/moderation/components/EmptyState";
import Button from "@library/forms/Button";
import PanelWidget from "@library/layout/components/PanelWidget";
import { List } from "@library/lists/List";
import { QueryLoader } from "@library/loaders/QueryLoader";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n";

function CustomPagesManagementImpl() {
    const { setPageToEdit } = useCustomPageContext();

    return (
        <AppearanceAdminLayout
            title={t("Custom Pages")}
            titleBarActions={
                <Button
                    buttonType={"outline"}
                    onClick={() => {
                        setPageToEdit("new");
                    }}
                >
                    {t("Create Page")}
                </Button>
            }
            content={<CustomPagesManagementContent />}
            rightPanel={
                <>
                    <PanelWidget>
                        <h3>{t("Heads up!")}</h3>
                        <p>
                            {t(
                                "Create custom pages to promote programs or share custom content with your community. Choose who can view each page, manage SEO settings, and customize layouts with widgets.",
                            )}
                        </p>
                    </PanelWidget>
                    <PanelWidget>
                        <h3>{t("Need more help?")}</h3>
                        <SmartLink to={"https://success.vanillaforums.com/kb/articles/1797-custom-landing-pages"}>
                            {t("Custom Pages Documentation")}
                        </SmartLink>
                    </PanelWidget>
                </>
            }
        />
    );
}

export function CustomPagesManagementContent() {
    const classes = customPagesClasses.useAsHook();

    const customPagesQuery = useCustomPagesQuery({});
    return (
        <QueryLoader
            query={customPagesQuery}
            success={(pages) => {
                return (
                    <>
                        {!pages || pages.length === 0 ? (
                            <EmptyState
                                emojiSet={["🪑", "🕳️", "📭", "🌀", "🌌", "🌑", "⏳", "⌛", "🧩"]}
                                text={t("No custom pages")}
                                subtext={t("Your custom pages will appear here.")}
                            />
                        ) : (
                            <List className={classes.pageList}>
                                {pages
                                    .sort((a, b) => (a.customPageID > b.customPageID ? -1 : 1))
                                    .map((page) => (
                                        <CustomPagesUI.ListItem key={page.customPageID} {...page} />
                                    ))}
                            </List>
                        )}

                        <CustomPagesUI.DeleteConfirmation />
                        <CustomPagesUI.AddEditPageDetails />
                    </>
                );
            }}
        />
    );
}

export default function CustomPagesManagement() {
    return (
        <CustomPagesProvider>
            <CustomPagesManagementImpl />
        </CustomPagesProvider>
    );
}
