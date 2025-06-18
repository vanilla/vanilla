/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { ICommonControl, IControlProps, IDragAndDropControl, JsonSchema } from "@library/json-schema-forms";
import { ITreeItem } from "@library/tree/types";
import { IconType } from "@vanilla/icons";

export interface IFormTreeControlLoadableProps<ItemDataType = any> extends Omit<IControlProps, "control"> {
    control: IDragAndDropControl;
    getRowIcon?(treeItem: ITreeItem<ItemDataType>): IconType;
    asModal?: boolean;
}
