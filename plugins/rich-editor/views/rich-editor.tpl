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
        <div class="richEditor-embedBar">
            <ul class="richEditor-menuItems" role="menubar" aria-label="{t c="Inline Level Formatting Menu"}">
                <li class="richEditor-menuItem u-richEditorHiddenOnMobile" role="menuitem">
                    <button class="richEditor-button richEditor-embedButton" type="button" aria-pressed="false" disabled="disabled">
                        <svg class="richEditorButton-icon" viewBox="0 0 24 24">
                            <title>{t c="Emoji"}</title>
                            <path fill="currentColor" d="M12,4 C7.58168889,4 4,7.58168889 4,12 C4,16.4181333 7.58168889,20 12,20 C16.4183111,20 20,16.4181333 20,12 C20,7.58168889 16.4183111,4 12,4 Z M12,18.6444444 C8.33631816,18.6444444 5.35555556,15.6636818 5.35555556,12 C5.35555556,8.33631816 8.33631816,5.35555556 12,5.35555556 C15.6636818,5.35555556 18.6444444,8.33631816 18.6444444,12 C18.6444444,15.6636818 15.6636818,18.6444444 12,18.6444444 Z M10.7059556,10.2024889 C10.7059556,9.51253333 10.1466667,8.95324444 9.45671111,8.95324444 C8.76675556,8.95324444 8.20746667,9.51253333 8.20746667,10.2024889 C8.20746667,10.8924444 8.76675556,11.4517333 9.45671111,11.4517333 C10.1466667,11.4517333 10.7059556,10.8924444 10.7059556,10.2024889 Z M14.5432889,8.95306667 C13.8533333,8.95306667 13.2940444,9.51235556 13.2940444,10.2023111 C13.2940444,10.8922667 13.8533333,11.4515556 14.5432889,11.4515556 C15.2332444,11.4515556 15.7925333,10.8922667 15.7925333,10.2023111 C15.7925333,9.51235556 15.2332444,8.95306667 14.5432889,8.95306667 Z M14.7397333,14.1898667 C14.5767111,14.0812444 14.3564444,14.1256889 14.2471111,14.2883556 C14.2165333,14.3336889 13.4823111,15.4012444 11.9998222,15.4012444 C10.5198222,15.4012444 9.7856,14.3374222 9.75271111,14.2885333 C9.64444444,14.1256889 9.42471111,14.0803556 9.2608,14.1884444 C9.09688889,14.2963556 9.05155556,14.5169778 9.15964444,14.6810667 C9.19804444,14.7393778 10.1242667,16.1125333 11.9998222,16.1125333 C13.8752,16.1125333 14.8014222,14.7395556 14.84,14.6810667 C14.9477333,14.5173333 14.9027556,14.2983111 14.7397333,14.1898667 Z"></path>
                        </svg>
                    </button>
                </li>
                {if $editorData.hasUploadPermission}
                <li class="richEditor-menuItem" role="menuitem">
                    <button class="richEditor-button richEditor-embedButton richEditor-buttonUpload" type="button" aria-pressed="false" disabled="disabled">
                        <svg class="richEditorButton-icon" viewBox="0 0 24 24">
                            <title>{t c="Image"}</title>
                            <path fill="currentColor" fill-rule="nonzero" d="M3,5 L3,19 L21,19 L21,5 L3,5 Z M3,4 L21,4 C21.5522847,4 22,4.44771525 22,5 L22,19 C22,19.5522847 21.5522847,20 21,20 L3,20 C2.44771525,20 2,19.5522847 2,19 L2,5 C2,4.44771525 2.44771525,4 3,4 Z M4,18 L20,18 L20,13.7142857 L15.2272727,7.42857143 L10.5,13.7142857 L7.5,11.5 L4,16.5510204 L4,18 Z M7.41729323,10.2443609 C8.24572036,10.2443609 8.91729323,9.57278803 8.91729323,8.7443609 C8.91729323,7.91593378 8.24572036,7.2443609 7.41729323,7.2443609 C6.58886611,7.2443609 5.91729323,7.91593378 5.91729323,8.7443609 C5.91729323,9.57278803 6.58886611,10.2443609 7.41729323,10.2443609 Z"/>
                        </svg>
                        <input class="richEditor-upload" type="file" accept="image/gif, image/jpeg, image/jpg, image/png">
                    </button>
                </li>
                {/if}
                <li class="richEditor-menuItem" role="menuitem">
                    <button class="richEditor-button richEditor-embedButton" type="button" aria-pressed="false" disabled="disabled">
                        <svg class="richEditorButton-icon" viewBox="0 0 24 24">
                            <title>{t c="Embed"}</title>
                            <path d="M4,5a.944.944,0,0,0-1,.875v12.25A.944.944,0,0,0,4,19H20a.944.944,0,0,0,1-.875V5.875A.944.944,0,0,0,20,5ZM4,4H20a1.9,1.9,0,0,1,2,1.778V18.222A1.9,1.9,0,0,1,20,20H4a1.9,1.9,0,0,1-2-1.778V5.778A1.9,1.9,0,0,1,4,4ZM9.981,16.382l-4.264-3.7V11.645L9.981,7.45V9.126l-3.2,2.958,3.2,2.605Zm4.326-1.693,3.2-2.605-3.2-2.958V7.45l4.265,4.195v1.041l-4.265,3.7Z" style="fill: currentColor"/>
                        </svg>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</div>
