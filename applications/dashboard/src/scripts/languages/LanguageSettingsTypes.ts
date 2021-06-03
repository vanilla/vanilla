/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { JsonSchema } from "@vanilla/json-schema-forms";

export interface ITranslationService {
    type: string;
    name: string;
    isConfigured: boolean;
    configSchema: JsonSchema;
}
