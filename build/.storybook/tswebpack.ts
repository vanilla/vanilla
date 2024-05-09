/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { makeStoryConfig } from "../scripts/configs/makeStoryConfig";
import { getOptions } from "../scripts/buildOptions";
import EntryModel from "../scripts/utility/EntryModel";

export default async function startStorybook({ config, mode }: any) {
    const options = await getOptions();
    const entryModel = new EntryModel(options);
    await entryModel.init();
    const finalConfig = await makeStoryConfig(config, entryModel);
    return finalConfig;
}
