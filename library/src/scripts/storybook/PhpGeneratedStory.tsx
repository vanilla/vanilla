/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { loadTranslations } from "@vanilla/i18n";
import React, { useEffect, useRef } from "react";
import "../../../../build/entries/windowGlobalsKludge";
import "../../../../addons/themes/theme-foundation/src/scss/custom.scss";
import "../../../../applications/vanilla/src/scripts/entries/forum";
import "../../../../plugins/rich-editor/src/scripts/entries/forum";
import "../../../../resources/fonts/vanillicon/vanillicon.ttf";
import "../../../../applications/dashboard/design/images/defaulticon.png";
import { NO_WRAPPER_CONFIG, useStoryConfig } from "@library/storybook/StoryContext";
import { LoadStatus } from "@library/@types/api/core";
import { applySharedPortalContext } from "@vanilla/react-utils";
import { Provider } from "react-redux";
import getStore from "@library/redux/getStore";
import { compatibilityStyles, cssOut } from "@dashboard/compatibilityStyles";
import { applyCompatibilityIcons } from "@dashboard/compatibilityStyles/compatibilityIcons";
import { initAllUserContent } from "@library/content";
import { applyCompatibilityUserCards } from "@library/features/userCard/UserCard.compat";
import { setMeta, _executeReady } from "@library/utility/appUtils";
import { classNames } from "react-select/lib/utils";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

loadTranslations({});
const staticCssFiles = import.meta.glob(
    [
        "../../../../applications/*/design/**/*.css",
        "../../../../plugins/*/design/**/*.css",
        "../../../../resources/design/**/*.css",
    ],
    { query: "?url" },
);

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
            staleTime: Infinity,
        },
    },
});

async function resolveCssUrls(cssPaths: string[]) {
    const allModules: Array<Promise<unknown>> = [];
    for (const desiredCssPath of cssPaths) {
        for (const [key, module] of Object.entries(staticCssFiles)) {
            if (key.includes(desiredCssPath)) {
                allModules.push(module().then((m) => (m as any).default));
            }
        }
    }
    const allCssUrls = await Promise.all(allModules);
    return allCssUrls as string[];
}

export function PhpGeneratedStory(props: { html: string; bodyClasses: string; cssFiles: string[] }) {
    const { html, bodyClasses, cssFiles } = props;

    useStoryConfig({
        ...NO_WRAPPER_CONFIG,
        storeState: {
            users: {
                permissions: {
                    status: LoadStatus.SUCCESS,
                    data: {
                        isAdmin: true,
                        permissions: [],
                    },
                },
            },
        },
    });

    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        applySharedPortalContext((props) => {
            return (
                <QueryClientProvider client={queryClient}>
                    <Provider store={getStore()}>{props.children}</Provider>
                </QueryClientProvider>
            );
        });
    }, []);

    useEffect(() => {
        setMeta("themeFeatures.NewQuickLinks", true);

        resolveCssUrls(cssFiles)
            .then((resolvedCssUrls) => {
                for (const resolvedCssUrl of resolvedCssUrls.reverse()) {
                    const link = document.createElement("link");
                    link.setAttribute("href", resolvedCssUrl);
                    link.setAttribute("rel", "stylesheet");
                    document.head.insertBefore(link, document.head.firstElementChild!);
                }
            })
            .then(() => {
                compatibilityStyles();
                applyCompatibilityIcons();
                applyCompatibilityUserCards();
                initAllUserContent();
                // Kludge to prevent some flashing during loading.
                cssOut(".Flyout", { display: "none !important" });

                // Copy body classes.
                document.body.className = classNames(document.body.className, bodyClasses);

                _executeReady();
            });
    }, []);
    return <div ref={ref} dangerouslySetInnerHTML={{ __html: html }} />;
}
