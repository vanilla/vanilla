import { createLoadableComponent } from "@vanilla/react-utils";
import { VanillaEditorContainer } from "@library/vanilla-editor/VanillaEditorContainer";
import { ensureBuiltinEmbeds } from "@library/embeddedContent/embedService.mounting";

async function loadVanillaEditorModule(): Promise<typeof import("./VanillaEditor.loadable")> {
    await ensureBuiltinEmbeds();

    return import("./VanillaEditor.loadable");
}

export const VanillaEditor = createLoadableComponent({
    loadFunction: () => loadVanillaEditorModule().then((module) => module.VanillaEditorLoadable),
    fallback: VanillaEditorContainer,
});

export const LegacyFormVanillaEditor = createLoadableComponent({
    loadFunction: () => loadVanillaEditorModule().then((module) => module.LegacyFormVanillaEditorLoadable),
    fallback: VanillaEditorContainer,
});
