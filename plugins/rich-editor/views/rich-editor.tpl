<div class="richEditor isDisabled" aria-label="{t c="Type your message"}" data-id="{$editorData.editorID}" aria-describedby="{$editorData.editorDescriptionID}" role="textbox" aria-multiline="true">
    <p id="{$editorData.editorDescriptionID}" class="sr-only">
        {t c="richEditor.description.title"}
        {t c="richEditor.description.paragraphMenu"}
        {t c="richEditor.description.inlineMenu"}
        {t c="richEditor.description.embed"}
    </p>
    <div class="richEditor-frame InputBox">
        <div class="richEditor-textWrap">
            <div class="ql-editor richEditor-text userContent isDisabled" data-gramm="false" contenteditable="false" disabled="disabled" data-placeholder="Create a new post..." tabindex="0"></div>
        </div>
    </div>
</div>
