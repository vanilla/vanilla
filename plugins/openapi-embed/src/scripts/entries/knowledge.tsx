/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { EditorEmbedBar } from "@rich-editor/editor/EditorEmbedBar";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { registerEmbed } from "@library/embeddedContent/embedService";
import { OpenApiEmbed, IOpenApiEmbedData } from "../embed/OpenApiEmbed";
import { useEditor } from "@rich-editor/editor/context";
import EmbedInsertionModule from "@rich-editor/quill/EmbedInsertionModule";
import { OpenApiForm } from "@openapi-embed/embed/OpenApiForm";
import { SwaggerIcon } from "@openapi-embed/embed/swagger/SwaggerIcon";
import classNames from "classnames";
import { richEditorClasses } from "@rich-editor/editor/richEditorStyles";
import { t } from "@vanilla/i18n";

registerEmbed("openapi", OpenApiEmbed);

function InsertOpenApiEmbedButton() {
    const { quill } = useEditor();
    const [showForm, setShowForm] = useState(false);
    const embedInserter = quill && (quill.getModule("embed/insertion") as EmbedInsertionModule);
    const classes = richEditorClasses(false);

    const insertEmbed = (data: IOpenApiEmbedData) => {
        if (!embedInserter) {
            return;
        }

        setShowForm(false);

        embedInserter.createEmbed({
            loaderData: {
                type: "image",
            },
            data,
        });
    };

    return (
        <>
            <EditorEmbedBar.Item>
                <Button
                    className={classNames(classes.button, "richEditor-button richEditor-embedButton")}
                    baseClass={ButtonTypes.CUSTOM}
                    onClick={() => {
                        setShowForm(true);
                    }}
                >
                    <span className={classes.iconWrap}></span>
                    <SwaggerIcon
                        className={classNames(classes.icon, "richEditorButton-icon")}
                        title={t("Add OpenApi Embed")}
                    />
                </Button>
            </EditorEmbedBar.Item>
            <OpenApiForm
                isVisible={showForm}
                data={{}}
                onSave={insertEmbed}
                onDismiss={() => {
                    setShowForm(false);
                }}
            />
        </>
    );
}

EditorEmbedBar.addExtraButton(<InsertOpenApiEmbedButton />);
