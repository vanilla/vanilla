/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */
import { useState, useEffect } from "react";
import { useEditorState, isElementEmpty } from "@udecode/plate-common";
import { cx } from "@emotion/css";
import { t } from "@library/utility/appUtils";
import { vanillaEditorClasses } from "@library/vanilla-editor/VanillaEditor.classes";

export default function VanillaEditorPlaceholder() {
    const editorState = useEditorState();
    const [showPlaceholder, setShowPlaceholder] = useState(true);

    const classes = vanillaEditorClasses.useAsHook();

    useEffect(() => {
        if (editorState.children.length === 1 && isElementEmpty(editorState, editorState.children[0] as any)) {
            setShowPlaceholder(true);
        } else {
            setShowPlaceholder(false);
        }
    }, [editorState.children]);

    return (
        <>
            <div className={cx(classes.placeholder, { hidden: !showPlaceholder })}>{t("Type...")}</div>
        </>
    );
}
