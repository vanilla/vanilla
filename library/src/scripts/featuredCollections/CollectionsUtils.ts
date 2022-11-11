/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { stableObjectHash } from "@vanilla/utils";
import { ICollectionResource } from "@library/featuredCollections/Collections.variables";

export function getResourceHash(resource: ICollectionResource) {
    const { recordID, recordType } = resource;
    return stableObjectHash([recordType, recordID]);
}
