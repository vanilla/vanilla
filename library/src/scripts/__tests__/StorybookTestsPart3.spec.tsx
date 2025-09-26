/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { expectStoriesToRender, iterateStoriesModules } from "@library/__tests__/StorybookTests.utils";

describe("Storybook Chunk 3", async () => {
    await iterateStoriesModules(3, 2, async (name, module) => {
        it(
            `Stories - <${name}/>`,
            async () => {
                await expectStoriesToRender(module);
            },
            { timeout: 100000 },
        );
    });
});
