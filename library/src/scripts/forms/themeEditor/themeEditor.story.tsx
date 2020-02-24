/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { storyWithConfig } from "@library/storybook/StoryContext";
import ThemeEditorInputBlock from "@library/forms/themeEditor/ThemeEditorInputBlock";
import ColorPicker from "@library/forms/themeEditor/ColorPicker";
import { percent } from "csx";
import { storiesOf } from "@storybook/react";
import ColorPickerBlock from "@library/forms/themeEditor/ColorPickerBlock";
import { Form, FormikProvider, useFormik } from "formik";

const story = storiesOf("Theme", module);

story.add("Theme Editor", () => {
    const form = useFormik({
        initialValues: {},
        onSubmit: values => {
            console.log(values);
        },
    });

    return (
        <StoryContent>
            <StoryHeading depth={1}>Theme Editor</StoryHeading>
            <div style={{ width: percent(100), maxWidth: "500px", margin: "auto" }}>
                <FormikProvider value={form}>
                    <Form>
                        <ColorPickerBlock
                            colorPicker={{ variableID: "global.something.or.other.color" }}
                            inputBlock={{ label: "testma" }}
                        />
                    </Form>
                </FormikProvider>
            </div>
        </StoryContent>
    );
});
