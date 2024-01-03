/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { withNormalizeTable } from "@library/vanilla-editor/plugins/tablePlugin/withNormalizeTable";
import { PlateEditor, Value, WithPlatePlugin } from "@udecode/plate-common";
import {
    TablePlugin,
    withDeleteTable,
    withGetFragmentTable,
    withInsertFragmentTable,
    withInsertTextTable,
    withSelectionTable,
} from "@udecode/plate-table";

export const withTable = <V extends Value = Value, E extends PlateEditor<V> = PlateEditor<V>>(
    editor: E,
    plugin: WithPlatePlugin<TablePlugin<V>, V, E>,
) => {
    editor = withNormalizeTable<V, E>(editor);
    editor = withDeleteTable<V, E>(editor);
    editor = withGetFragmentTable<V, E>(editor);
    editor = withInsertFragmentTable<V, E>(editor, plugin);
    editor = withInsertTextTable<V, E>(editor, plugin);
    editor = withSelectionTable<V, E>(editor);

    return editor;
};
