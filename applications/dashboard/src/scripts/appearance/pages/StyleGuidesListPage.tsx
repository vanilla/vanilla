/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { AppearanceNav } from "@dashboard/appearance/nav/AppearanceNav";
import AdminLayout from "@dashboard/components/AdminLayout";
import AdminTitleBar from "@dashboard/components/AdminTitleBar";
import { css, cx } from "@emotion/css";
import { userContentClasses } from "@library/content/UserContent.styles";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import SmartLink from "@library/routing/links/SmartLink";
import { ThemeList } from "@themingapi/theme-list/ThemeList";
import { t } from "@vanilla/i18n";
import { useCollisionDetector } from "@vanilla/react-utils";
import React, { ComponentType } from "react";

const styleGuideListPageClass = {
    content: css({
        "& div.subheading-title, & div.subheading-title + div": {
            padding: "8px 16px 16px",
        },
    }),
};

export default function Page() {
    const device = useTitleBarDevice();
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;
    return (
        <AdminLayout
            activeSectionID={"appearance"}
            title={t("Style Guides")}
            adminBarHamburgerContent={<AppearanceNav asHamburger />}
            leftPanel={!isCompact && <AppearanceNav />}
            content={<ThemeList />}
            contentClassNames={styleGuideListPageClass.content}
            rightPanel={
                <div>
                    <h3>{t("Heads Up!")}</h3>
                    <p>
                        {t(
                            "Welcome to Vanilla's theming UI.",
                            "Welcome to Vanilla's theming UI. This page lists all of your available themes, and allows you to copy or edit them.",
                        )}
                    </p>
                    <p>
                        {t(
                            "Some older themes don't support full editing capability.",
                            "Some older themes don't support full editing capability. To see what a theme supports you can hover over its name to see where edits will take effect.",
                        )}
                    </p>
                    <h3>{t("Need More Help?")}</h3>
                    <p>
                        <SmartLink to={"https://success.vanillaforums.com/kb/theme-guide"}>
                            {t("Theming Guide")}
                        </SmartLink>
                    </p>
                    <h3>{t("Old Theming UI")}</h3>
                    <p>
                        {t(
                            "If you have an old theme",
                            "If you have an old theme and need to set a separate desktop and mobile theme you can do so with the old theming UI.",
                        )}
                    </p>
                    <p>
                        <SmartLink to={"/settings/themes"}>{t("Old Theming UI")}</SmartLink>
                    </p>
                </div>
            }
        />
    );
}
