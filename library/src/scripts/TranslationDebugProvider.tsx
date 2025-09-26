/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { translationDebug } from "@vanilla/i18n";
import { createLoadableComponent } from "@vanilla/react-utils";

const TranslationDebugger = createLoadableComponent({
    loadFunction: () => import("./TranslationDebugger.loadable"),
    fallback: () => <></>,
});

export function TranslationDebugProvider(props: { children?: React.ReactNode }) {
    return (
        <>
            {translationDebug() && <TranslationDebugger />}
            {props.children}
        </>
    );
}
