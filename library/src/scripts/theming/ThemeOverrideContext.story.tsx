/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { DiscussionListView } from "@library/features/discussions/DiscussionList.views";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { StoryContent } from "@library/storybook/StoryContent";
import type { IStoryTheme } from "@library/storybook/StoryContext";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { ThemeOverrideContext, useWithThemeContext } from "@library/theming/ThemeOverrideContext";
import { setMeta } from "@library/utility/appUtils";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { DiscussionFixture } from "@vanilla/addon-vanilla/posts/__fixtures__/Discussion.Fixture";

setMeta("tagging.enabled", true);

export default {
    title: "Components/Theme Overridde",
};

export function AreasWithDifferentThemes() {
    return (
        <>
            <ThingToRender title="Default Theme" />
            <ThemeOverrideContext.Provider
                value={{
                    overridesVariables: {
                        global: {
                            options: {
                                preset: "dark",
                            },
                        },
                    } as IStoryTheme,
                    themeID: "dark",
                }}
            >
                <ThingToRender title="Dark Theme" />
            </ThemeOverrideContext.Provider>
        </>
    );
}

const queryClient = new QueryClient();

function ThingToRender(props: { title?: string }) {
    const globalVars = useWithThemeContext(globalVariables);

    return (
        <StoryContent>
            <div style={{ paddingTop: 24, paddingBottom: 24 }}>
                <PageHeadingBox title={props.title} />
                {/* <div className={css({ ...Mixins.background({ color: globalVars.mainColors.bg }) })}> */}
                <QueryClientProvider client={queryClient}>
                    <PermissionsFixtures.AllPermissions>
                        <DiscussionListView discussions={DiscussionFixture.fakeDiscussions}></DiscussionListView>
                    </PermissionsFixtures.AllPermissions>
                </QueryClientProvider>
            </div>
        </StoryContent>
    );
}
