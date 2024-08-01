/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { expectStoriesToRender, iterateStoriesModules } from "@library/__tests__/StorybookTests.utils";

describe("Storybook Chunk 2", async () => {
    await iterateStoriesModules(3, 1, async (name, module) => {
        it(`Stories - <${name}/>`, async () => {
            await expectStoriesToRender(module);
        });
    });
});
