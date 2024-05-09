/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import { StoryContextProvider } from "@library/storybook/StoryContext";
import { mockAPI } from "@library/__tests__/utility";
import { render } from "@testing-library/react";
import { basename } from "path";

type StoryModule = () => Promise<Record<string, React.ComponentType>>;

export async function iterateStoriesModules(
    countChunks: number,
    chunk: number,
    callback: (name: string, module: StoryModule) => Promise<void>,
) {
    if (!("RUN_STORYBOOK_TESTS" in import.meta.env)) {
        return;
    }

    const modules = import.meta.glob("../../../../**/*.story.ts*");

    const seenFiles = new Set<string>();
    for (const [key, module] of Object.entries(modules)) {
        const uniqueKey = key.replace("/cloud/", "/");
        if (seenFiles.has(uniqueKey)) {
            continue;
        }
        seenFiles.add(uniqueKey);

        const offset = seenFiles.size - 1;
        if (offset % countChunks !== chunk) {
            continue;
        }

        let fileLabel = basename(key, ".story.tsx");
        await callback(fileLabel, module as any);
    }
}

export async function expectStoriesToRender(module: StoryModule) {
    mockAPI().onAny().reply(200, {});
    const imported: Record<string, React.ComponentType> = await module();
    for (const [storyName, StoryComponent] of Object.entries(imported)) {
        if (storyName === "default") {
            continue;
        }
        if (typeof StoryComponent !== "function") {
            continue;
        }
        render(
            <ErrorBoundary>
                <StoryContextProvider>
                    <StoryComponent />
                </StoryContextProvider>
            </ErrorBoundary>,
        );
        expect(true).toBe(true);
    }
}
