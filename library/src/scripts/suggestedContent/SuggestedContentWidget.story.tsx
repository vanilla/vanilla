/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import SuggestedContentWidget from "@library/suggestedContent/SuggestedContentWidget";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

export default {
    title: "Widgets/SuggestedContent",
};

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
            enabled: false,
            staleTime: Infinity,
        },
    },
});

function WidgetRender(
    props: Omit<React.ComponentProps<typeof SuggestedContentWidget>, "discussions" | "categories" | "apiParams">,
) {
    return (
        <QueryClientProvider client={queryClient}>
            <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                <SuggestedContentWidget
                    discussions={LayoutEditorPreviewData.discussions(5)}
                    categories={LayoutEditorPreviewData.categories(5)}
                    apiParams={{}}
                    {...props}
                />
            </CurrentUserContextProvider>
        </QueryClientProvider>
    );
}

export function Default() {
    return (
        <WidgetRender
            title={"Widget Title"}
            subtitle={"Widget Subtitle"}
            description={"Widget Description"}
            suggestedContent={{
                enabled: true,
            }}
            suggestedFollows={{
                enabled: true,
            }}
        />
    );
}

export function CategoriesOnly() {
    return (
        <WidgetRender
            title={"Widget Title"}
            subtitle={"Widget Subtitle"}
            description={"Widget Description"}
            suggestedContent={{
                enabled: false,
            }}
            suggestedFollows={{
                enabled: true,
            }}
        />
    );
}

export function DiscussionsOnly() {
    return (
        <WidgetRender
            title={"Widget Title"}
            subtitle={"Widget Subtitle"}
            description={"Widget Description"}
            suggestedContent={{
                enabled: true,
            }}
            suggestedFollows={{
                enabled: false,
            }}
        />
    );
}

export function DoubleHeadings() {
    return (
        <WidgetRender
            title={"Widget Title"}
            subtitle={"Widget Subtitle"}
            description={"Widget Description"}
            suggestedContent={{
                enabled: true,
                title: "Suggested Content Title",
                subtitle: "Suggested Content Subtitle",
            }}
            suggestedFollows={{
                enabled: true,
                title: "Suggested Follows Title",
                subtitle: "Suggested Follows Subtitle",
            }}
        />
    );
}
