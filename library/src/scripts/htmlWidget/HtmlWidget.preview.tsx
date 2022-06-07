/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { HtmlWidget } from "@library/htmlWidget/HtmlWidget";
import { htmlWidgetEditorClasses } from "@library/htmlWidget/HtmlWidgetEditor.classes";
import { CodeBlockIcon } from "@library/icons/editorIcons";
import { PageBox } from "@library/layout/PageBox";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { Widget } from "@library/layout/Widget";
import { BorderType } from "@library/styles/styleHelpersBorders";
import React from "react";

export function HtmlWidgetPreview(props: React.ComponentProps<typeof HtmlWidget>) {
    const classes = htmlWidgetEditorClasses();
    return (
        <Widget>
            <PageBox options={{ borderType: BorderType.SHADOW }}>
                <h3>{`Custom HTML - ${props.name ?? "Untitled"}`}</h3>
                <CodeBlockIcon className={classes.previewIcon} />
            </PageBox>
        </Widget>
    );
}
