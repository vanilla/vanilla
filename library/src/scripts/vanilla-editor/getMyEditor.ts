import type { MyEditor, MyOverrideByKey, MyPlatePlugin, MyValue } from "@library/vanilla-editor/typescript";
import {
    createPlateEditor,
    createPlugins,
    createTEditor,
    getTEditor,
    useEditorRef,
    useEditorState,
    usePlateEditorRef,
    usePlateEditorState,
    usePlateSelectors,
    type CreatePlateEditorOptions,
    type PlateId,
    type PlatePluginComponent,
} from "@udecode/plate-common";

/**
 * Plate store, Slate context
 */

export const getMyEditor = (editor: MyEditor) => getTEditor<MyValue, MyEditor>(editor);
export const useMyEditorRef = () => useEditorRef<MyValue, MyEditor>();
export const useMyEditorState = () => useEditorState<MyValue, MyEditor>();
export const useMyPlateEditorRef = (id?: PlateId) => usePlateEditorRef<MyValue, MyEditor>(id);
export const useMyPlateEditorState = (id?: PlateId) => usePlateEditorState<MyValue, MyEditor>(id);
export const useMyPlateSelectors = (id?: PlateId) => usePlateSelectors<MyValue, MyEditor>(id);
/**
 * Utils
 */

export const createMyEditor = () => createTEditor() as MyEditor;
export const createMyPlateEditor = (options: CreatePlateEditorOptions<MyValue, MyEditor> = {}) =>
    createPlateEditor<MyValue, MyEditor>(options);
export const createMyPlugins = (
    plugins: MyPlatePlugin[],
    options?: {
        components?: Record<string, PlatePluginComponent>;
        overrideByKey?: MyOverrideByKey;
    },
) => createPlugins<MyValue, MyEditor>(plugins, options);
