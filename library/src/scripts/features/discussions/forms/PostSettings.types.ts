/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { PostField } from "@dashboard/postTypes/postType.types";

export interface IPostSettingsProps {
    discussion: IDiscussion;
    onClose: () => void;
    initialAction: "move" | "change" | null;
    handleSuccess?: () => Promise<void>;
    isLegacyPage?: boolean;
}

export interface PostFieldMap {
    currentField: PostField["postFieldID"];
    targetField: PostField["postFieldID"];
    currentFieldValue: any;
    targetFieldValue: any;
}
