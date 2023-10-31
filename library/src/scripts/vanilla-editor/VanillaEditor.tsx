import { createLoadableComponent } from "@vanilla/react-utils";
import { VanillaEditorContainer } from "@library/vanilla-editor/VanillaEditorContainer";

export const VanillaEditor = createLoadableComponent({
    loadFunction: () => import("./VanillaEditor.loadable").then((module) => module.VanillaEditorLoadable),
    fallback: VanillaEditorContainer,
});
