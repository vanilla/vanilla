/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { EditorEmbedBar } from "@rich-editor/editor/EditorEmbedBar";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { registerEmbed } from "@library/embeddedContent/embedService";
import { OpenApiEmbed } from "../embed/OpenApiEmbed";
import { useEditor } from "@rich-editor/editor/context";
import EmbedInsertionModule from "@rich-editor/quill/EmbedInsertionModule";

registerEmbed("openapi", OpenApiEmbed);

function InsertOpenApiEmbedButton() {
    const { quill } = useEditor();
    const embedInserter = quill && (quill.getModule("embed/insertion") as EmbedInsertionModule);

    const clickHandler = () => {
        if (!embedInserter) {
            return;
        }

        embedInserter.createEmbed({
            loaderData: {
                type: "image",
            },
            data: {
                embedType: "openapi",
                url: "https://dev.vanilla.localhost/api/v2/openapi/v3",
            },
        });
    };

    return (
        <EditorEmbedBar.Item>
            <Button baseClass={ButtonTypes.TEXT} onClick={clickHandler}>
                OpenAPI
            </Button>
        </EditorEmbedBar.Item>
    );
}

EditorEmbedBar.addAdditionItem(<InsertOpenApiEmbedButton />);
