/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

export interface IBulkActionForm {
    onCancel(): void;
    onSuccess(): void;
}

export enum BulkAction {
    DELETE = "delete",
    MERGE = "merge",
    MOVE = "move",
    CLOSE = "close",
    SPLIT = "split",
    WARN = "warn",
}

export type BulkActionMessages =
    | "selectedRecordsCount"
    | "minimalSelection"
    | "editPermission"
    | "closePermission"
    | "deletePermission";

export interface IBulkActionButton {
    action: BulkAction;
    notAllowed: boolean;
    handler: () => void;
    notAllowedMessage: string;
}

export interface IAdditionalBulkAction extends Pick<IBulkActionButton, "action" | "notAllowedMessage"> {
    permission: string;
    contentRenderer: React.ComponentType<IBulkActionForm>;
}
