import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import { StoryContent } from "@library/storybook/StoryContent";
import React from "react";
import TextEditor from "@library/textEditor/TextEditor";

const story = storiesOf("ContentEditor", module);

story.add("TextEditor", () => {
    return (
        <StoryContent>
            <StoryHeading>Sample Text Editor</StoryHeading>
            <TextEditor
                height={"90vh"} // By default, it fully fits with its parent
                theme={"dark"}
                language={"html"}
                // editorDidMount={handleEditorDidMount}
                options={{ lineNumbers: "on" }}
            />
        </StoryContent>
    );
});
