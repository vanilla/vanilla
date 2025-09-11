/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { mountModal } from "@library/modal/mountModal";
import { createLoadableComponent } from "@vanilla/react-utils";

const ChunkDebugger = createLoadableComponent({
    loadFunction() {
        return import("./ChunkDebugger.loadable");
    },
    fallback: () => null,
});

export function conditionallyMountChunkDebugger() {
    const chunks = window.__VANILLA_CHUNK_DEBUGGER__ ?? [];
    if (chunks.length === 0) {
        return;
    }
    void mountModal(<ChunkDebugger />);
}

declare global {
    interface Window {
        __VANILLA_CHUNK_DEBUGGER__?: string[];
        __VANILLA_BUILD_SECTION__: string;
    }
}
