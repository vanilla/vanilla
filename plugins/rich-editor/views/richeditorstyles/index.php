<div class="richEditor" aria-label="<?php echo t('Type your message');?>" aria-describedby="richEditor-description" role="textbox" aria-multiline="true">
    <p id="richEditor-description" class="sr-only">
        <?php echo t('Insert instructions for editor here'); ?>
    </p>
    <div class="richEditor-frame InputBox">
        <div class="js-richText">
            <div class="ql-editor richEditor-text userContent" data-gramm="false" contenteditable="true" data-placeholder="Create a new post..."></div>
        </div>
        <div class="richEditor-menu richEditorParagraphMenu">
            <button class="richEditor-button richEditor-embedButton richEditorParagraphMenu-handle" type="button" aria-haspopup="menu" aria-expanded="false" aria-controls="tempId-paragraphLevelMenu-toggle">
                <svg class="richEditorButton-icon" viewBox="0 0 24 24">
                    <title>¶</title>
                    <path fill="currentColor" fill-rule="evenodd" d="M15,6 L17,6 L17,18 L15,18 L15,6 Z M11,6 L13.0338983,6 L13.0338983,18 L11,18 L11,6 Z M11,13.8666667 C8.790861,13.8666667 7,12.1056533 7,9.93333333 C7,7.76101332 8.790861,6 11,6 C11,7.68571429 11,11.6190476 11,13.8666667 Z"/>
                </svg>
            </button>


            <!-- Paragraph level menu goes here -->
        </div>

        <div class="richEditor-embedBar">
            <ul class="richEditor-menuItems" role="menubar" aria-label="<?php echo t('Inline Level Formatting Menu'); ?>">
                <li class="richEditor-menuItem" role="menuitem">
                    <button class="richEditor-button richEditor-embedButton" type="button" aria-pressed="false">
                        <svg class="richEditorButton-icon" viewBox="0 0 24 24">
                            <title><?php echo t('Emoji'); ?></title>
                            <path fill="currentColor" d="M12,4 C7.58168889,4 4,7.58168889 4,12 C4,16.4181333 7.58168889,20 12,20 C16.4183111,20 20,16.4181333 20,12 C20,7.58168889 16.4183111,4 12,4 Z M12,18.6444444 C8.33631816,18.6444444 5.35555556,15.6636818 5.35555556,12 C5.35555556,8.33631816 8.33631816,5.35555556 12,5.35555556 C15.6636818,5.35555556 18.6444444,8.33631816 18.6444444,12 C18.6444444,15.6636818 15.6636818,18.6444444 12,18.6444444 Z M10.7059556,10.2024889 C10.7059556,9.51253333 10.1466667,8.95324444 9.45671111,8.95324444 C8.76675556,8.95324444 8.20746667,9.51253333 8.20746667,10.2024889 C8.20746667,10.8924444 8.76675556,11.4517333 9.45671111,11.4517333 C10.1466667,11.4517333 10.7059556,10.8924444 10.7059556,10.2024889 Z M14.5432889,8.95306667 C13.8533333,8.95306667 13.2940444,9.51235556 13.2940444,10.2023111 C13.2940444,10.8922667 13.8533333,11.4515556 14.5432889,11.4515556 C15.2332444,11.4515556 15.7925333,10.8922667 15.7925333,10.2023111 C15.7925333,9.51235556 15.2332444,8.95306667 14.5432889,8.95306667 Z M14.7397333,14.1898667 C14.5767111,14.0812444 14.3564444,14.1256889 14.2471111,14.2883556 C14.2165333,14.3336889 13.4823111,15.4012444 11.9998222,15.4012444 C10.5198222,15.4012444 9.7856,14.3374222 9.75271111,14.2885333 C9.64444444,14.1256889 9.42471111,14.0803556 9.2608,14.1884444 C9.09688889,14.2963556 9.05155556,14.5169778 9.15964444,14.6810667 C9.19804444,14.7393778 10.1242667,16.1125333 11.9998222,16.1125333 C13.8752,16.1125333 14.8014222,14.7395556 14.84,14.6810667 C14.9477333,14.5173333 14.9027556,14.2983111 14.7397333,14.1898667 Z"/>
                        </svg>
                    </button>
                </li>
                <li class="richEditor-menuItem" role="menuitem">
                    <button class="richEditor-button richEditor-embedButton" type="button" aria-pressed="false">
                        <svg class="richEditorButton-icon" viewBox="0 0 24 24">
                            <title><?php echo t('Image'); ?></title>
                            <path fill="currentColor" fill-rule="nonzero" d="M3,5 L3,19 L21,19 L21,5 L3,5 Z M3,4 L21,4 C21.5522847,4 22,4.44771525 22,5 L22,19 C22,19.5522847 21.5522847,20 21,20 L3,20 C2.44771525,20 2,19.5522847 2,19 L2,5 C2,4.44771525 2.44771525,4 3,4 Z M4,18 L20,18 L20,13.7142857 L15.2272727,7.42857143 L10.5,13.7142857 L7.5,11.5 L4,16.5510204 L4,18 Z M7.41729323,10.2443609 C8.24572036,10.2443609 8.91729323,9.57278803 8.91729323,8.7443609 C8.91729323,7.91593378 8.24572036,7.2443609 7.41729323,7.2443609 C6.58886611,7.2443609 5.91729323,7.91593378 5.91729323,8.7443609 C5.91729323,9.57278803 6.58886611,10.2443609 7.41729323,10.2443609 Z"/>
                        </svg>
                    </button>
                </li>

                <li class="richEditor-menuItem" role="menuitem">
                    <button class="richEditor-button richEditor-embedButton" type="button" aria-pressed="false">
                        <svg class="richEditorButton-icon" viewBox="0 0 24 24">
                            <title><?php echo t('HTML View'); ?></title>
                            <path d="M4,5a.944.944,0,0,0-1,.875v12.25A.944.944,0,0,0,4,19H20a.944.944,0,0,0,1-.875V5.875A.944.944,0,0,0,20,5ZM4,4H20a1.9,1.9,0,0,1,2,1.778V18.222A1.9,1.9,0,0,1,20,20H4a1.9,1.9,0,0,1-2-1.778V5.778A1.9,1.9,0,0,1,4,4ZM9.981,16.382l-4.264-3.7V11.645L9.981,7.45V9.126l-3.2,2.958,3.2,2.605Zm4.326-1.693,3.2-2.605-3.2-2.958V7.45l4.265,4.195v1.041l-4.265,3.7Z" style="fill: currentColor"/>
                        </svg>
                    </button>
                </li>
                <li class="richEditor-menuItem isRightAligned" role="menuitem">
                    <button class="richEditor-button richEditor-embedButton" type="button" aria-pressed="false">
                        <svg class="richEditorButton-icon" viewBox="0 0 24 24">
                            <title><?php echo t('Help'); ?></title>
                            <path fill="currentColor" d="M12,19 C15.8659932,19 19,15.8659932 19,12 C19,8.13400675 15.8659932,5 12,5 C8.13400675,5 5,8.13400675 5,12 C5,15.8659932 8.13400675,19 12,19 Z M12,20 C7.581722,20 4,16.418278 4,12 C4,7.581722 7.581722,4 12,4 C16.418278,4 20,7.581722 20,12 C20,16.418278 16.418278,20 12,20 Z M11.1336706,13.4973545 L11.1336706,13.1587302 C11.1336706,12.7707212 11.2042167,12.4479731 11.3453108,12.1904762 C11.486405,11.9329793 11.7333161,11.666668 12.0860516,11.3915344 C12.5058068,11.0599631 12.7765272,10.8024701 12.8982209,10.6190476 C13.0199146,10.4356252 13.0807606,10.2169325 13.0807606,9.96296296 C13.0807606,9.66666519 12.9819961,9.43915423 12.7844643,9.28042328 C12.5869324,9.12169233 12.3029847,9.04232804 11.9326124,9.04232804 C11.5975138,9.04232804 11.2871112,9.08994661 11.0013955,9.18518519 C10.7156798,9.28042376 10.437023,9.39506106 10.1654167,9.52910053 L9.72097222,8.5978836 C10.4370252,8.19929254 11.2042133,8 12.0225595,8 C12.713921,8 13.2624164,8.16931048 13.6680622,8.50793651 C14.0737079,8.84656254 14.2765278,9.31393 14.2765278,9.91005291 C14.2765278,10.1746045 14.2377275,10.4100519 14.1601257,10.6164021 C14.0825239,10.8227524 13.9652411,11.0193994 13.8082738,11.2063492 C13.6513065,11.393299 13.3805861,11.6366828 12.9961045,11.9365079 C12.6680605,12.1940048 12.448486,12.4074066 12.3373743,12.5767196 C12.2262627,12.7460326 12.1707077,12.9735435 12.1707077,13.2592593 L12.1707077,13.4973545 L11.1336706,13.4973545 Z M10.9167394,15.1851852 C10.9167394,14.6525547 11.1759961,14.3862434 11.6945172,14.3862434 C11.9484867,14.3862434 12.1424883,14.4559076 12.2765278,14.5952381 C12.4105672,14.7345686 12.477586,14.9312157 12.477586,15.1851852 C12.477586,15.4356274 12.4096854,15.6340381 12.2738823,15.7804233 C12.1380791,15.9268085 11.9449594,16 11.6945172,16 C11.444075,16 11.2518371,15.9285721 11.1177976,15.7857143 C10.9837581,15.6428564 10.9167394,15.4426821 10.9167394,15.1851852 Z"/>
                        </svg>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</div>

<hr/>

<h2>@mentions - Component (in editor)</h2>

<h3>List</h3>


<div style="position: relative; height: 200px;"> <!-- Div for styleguide only -->


    <span class="atMentionList">
        <ul
          id="[idOfAtMentionMenu]"
          aria-label="{t('@mention user list')}"
          class="atMentionList-items MenuItems"
          role="listbox"
        >
            <li
              id="[idOfAtMentionMenu-item1]"
              class="richEditor-menuItem atMentionList-item isActive"
              role="option"
              aria-selected="true"
            >
                <button class="atMentionList-suggestion">
                    <span class="atMentionList-user">
                        <span class="PhotoWrap atMentionList-photoWrap">
                            <img src="https://secure.gravatar.com/avatar/b0420af06d6fecc16fc88a88cbea8218/?default=https%3A%2F%2Fvanillicon.com%2Fb0420af06d6fecc16fc88a88cbea8218_200.png&amp;rating=g&amp;size=120" alt="Linc" class="atMentionList-photo ProfilePhoto">
                        </span>
                        <span class="atMentionList-userName">
                            <mark class="atMentionList-mark">Fra</mark>nkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkk
                        </span>
                    </span>
                </button>
            </li>
            <li
              id="[idOfAtMentionMenu-item2]"
              class="richEditor-menuItem atMentionList-item"
              role="option"
              aria-selected="false"
            >
                <button class="atMentionList-suggestion">
                    <span class="atMentionList-user">
                        <span class="PhotoWrap atMentionList-photoWrap">
                            <img src="https://secure.gravatar.com/avatar/b0420af06d6fecc16fc88a88cbea8218/?default=https%3A%2F%2Fvanillicon.com%2Fb0420af06d6fecc16fc88a88cbea8218_200.png&amp;rating=g&amp;size=120" alt="Linc" class="atMentionList-photo ProfilePhoto">
                        </span>
                        <span class="atMentionList-userName">
                            <mark class="atMentionList-mark">Fra</mark>nkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkk
                        </span>
                    </span>
                </button>
            </li>
            <li
              id="[idOfAtMentionMenu-item3]"
              class="richEditor-menuItem atMentionList-item"
              role="option"
              aria-selected="false"
            >
                <button class="atMentionList-suggestion">
                    <span class="atMentionList-user">
                        <span class="PhotoWrap atMentionList-photoWrap">
                            <img src="https://secure.gravatar.com/avatar/b0420af06d6fecc16fc88a88cbea8218/?default=https%3A%2F%2Fvanillicon.com%2Fb0420af06d6fecc16fc88a88cbea8218_200.png&amp;rating=g&amp;size=120" alt="Linc" class="atMentionList-photo ProfilePhoto">
                        </span>
                        <span class="atMentionList-userName">
                            <mark class="atMentionList-mark">Fra</mark>nkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkk
                        </span>
                    </span>
                </button>
            </li>
            <li
              id="[idOfAtMentionMenu-item4]"
              class="richEditor-menuItem atMentionList-item"
              role="option"
              aria-selected="false"
            >
                <button class="atMentionList-suggestion">
                    <span class="atMentionList-user">
                        <span class="PhotoWrap atMentionList-photoWrap">
                            <img src="https://secure.gravatar.com/avatar/b0420af06d6fecc16fc88a88cbea8218/?default=https%3A%2F%2Fvanillicon.com%2Fb0420af06d6fecc16fc88a88cbea8218_200.png&amp;rating=g&amp;size=120" alt="Linc" class="atMentionList-photo ProfilePhoto">
                        </span>
                        <span class="atMentionList-userName">
                            <mark class="atMentionList-mark">Fra</mark>nkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkk
                        </span>
                    </span>
                </button>
            </li>
            <li
              id="[idOfAtMentionMenu-item5]"
              class="richEditor-menuItem atMentionList-item"
              role="option"
              aria-selected="false"
            >
                <button class="atMentionList-suggestion">
                    <span class="atMentionList-user">
                        <span class="PhotoWrap atMentionList-photoWrap">
                            <img src="https://secure.gravatar.com/avatar/b0420af06d6fecc16fc88a88cbea8218/?default=https%3A%2F%2Fvanillicon.com%2Fb0420af06d6fecc16fc88a88cbea8218_200.png&amp;rating=g&amp;size=120" alt="Linc" class="atMentionList-photo ProfilePhoto">
                        </span>
                        <span class="atMentionList-userName">
                            <mark class="atMentionList-mark">Fra</mark>nkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkk
                        </span>
                    </span>
                </button>
            </li>
        </ul>
    </span>
</div>

<h3>Edit</h3>
<span
      id="[idOfAtMentionComboBox]"
      class="atMentionComboBox"
      role="combobox"
      aria-haspopup="listbox"
      aria-owns="[idOfAtMentionMenu]"
      aria-expanded="true"
      aria-activedescendant="[idOfAtMentionMenu-item1]"
>
    <span
      role="textbox"
      aria-label="{t('@mention a user')}"
      aria-autocomplete="both"
      aria-controls="[idOfAtMentionMenu]"
      aria-activedescendant="[idOfAtMentionMenu-item]"
    >
        Fra
    </span>
</span>


<h3>Rendered</h3>

<a href="#" class="atMention">
    @someUser
</a>

<hr/>


<h2>Paragraph Level Formatting Menu</h2>

<div class="richEditor-menu" role="menu" aria-label="<?php echo t('Paragraph Level Formatting Menu') ?>">
    <ul class="richEditor-menuItems" role="radiogroup">
        <li class="richEditor-menuItem" role="presentation">
            <button class="richEditor-button" type="button" role="radio" aria-checked="false">
                <svg class="richEditorButton-icon" viewBox="0 0 24 24">
                    <title><?php echo t('Title'); ?></title>
                    <path d="M12.3,17H10.658V12.5H6.051V17H4.417V7.006H6.051v4.088h4.607V7.006H12.3Zm5.944,0H16.637V10.547q0-1.155.055-1.832-.157.163-.387.362t-1.534,1.258l-.807-1.019L16.9,7.006h1.34Z" style="fill: currentColor"/>
                </svg>
            </button>
        </li>
        <li class="richEditor-menuItem" role="presentation">
            <button class="richEditor-button" type="button" role="radio" aria-checked="false">
                <svg class="richEditorButton-icon" viewBox="0 0 24 24">
                    <title><?php echo t('Subtitle'); ?></title>
                    <path d="M12.3,17H10.658V12.5H6.051V17H4.417V7.006H6.051v4.088h4.607V7.006H12.3Zm8,0H13.526V15.783L16.1,13.192a22.007,22.007,0,0,0,1.514-1.657,3.978,3.978,0,0,0,.543-.92,2.475,2.475,0,0,0,.171-.923,1.4,1.4,0,0,0-.407-1.066,1.557,1.557,0,0,0-1.124-.39,3,3,0,0,0-1.111.212,5.239,5.239,0,0,0-1.241.766l-.868-1.06a5.612,5.612,0,0,1,1.62-1,4.744,4.744,0,0,1,1.675-.294,3.294,3.294,0,0,1,2.235.728,2.46,2.46,0,0,1,.841,1.959,3.453,3.453,0,0,1-.242,1.285,5.212,5.212,0,0,1-.746,1.254,17.041,17.041,0,0,1-1.671,1.747l-1.736,1.682v.068H20.3Z" style="fill: currentColor"/>
                </svg>
            </button>
        </li>
        <li class="richEditor-menuItem" role="presentation">
            <button class="richEditor-button" type="button" role="radio" aria-checked="false">
                <svg class="richEditorButton-icon" viewBox="0 0 24 24">
                    <title><?php echo t('Quote'); ?></title>
                    <path d="M10.531,17.286V12.755H8.122a9.954,9.954,0,0,1,.1-1.408,4.22,4.22,0,0,1,.388-1.286,2.62,2.62,0,0,1,.735-.918A1.815,1.815,0,0,1,10.49,8.8V6.755a3.955,3.955,0,0,0-2,.49A4.164,4.164,0,0,0,7.082,8.551a5.84,5.84,0,0,0-.817,1.9A9.65,9.65,0,0,0,6,12.755v4.531Zm7.469,0V12.755H15.592a9.954,9.954,0,0,1,.1-1.408,4.166,4.166,0,0,1,.388-1.286,2.606,2.606,0,0,1,.734-.918A1.819,1.819,0,0,1,17.959,8.8V6.755a3.958,3.958,0,0,0-2,.49,4.174,4.174,0,0,0-1.408,1.306,5.86,5.86,0,0,0-.816,1.9,9.649,9.649,0,0,0-.266,2.306v4.531Z" style="fill: currentColor;"/>
                </svg>
            </button>
        </li>
        <li class="richEditor-menuItem" role="presentation">
            <button class="richEditor-button" type="button" role="radio" aria-checked="false">
                <svg class="richEditorButton-icon" viewBox="0 0 24 24">
                    <title><?php echo t('Paragraph Code Block'); ?></title>
                    <path fill="currentColor" fill-rule="evenodd" d="M9.11588626,16.5074223 L3.14440918,12.7070466 L3.14440918,11.6376386 L9.11588626,7.32465415 L9.11588626,9.04808032 L4.63575044,12.0883808 L9.11588626,14.7663199 L9.11588626,16.5074223 Z M14.48227,5.53936141 L11.1573124,18.4606386 L9.80043634,18.4606386 L13.131506,5.53936141 L14.48227,5.53936141 Z M15.1729321,14.7663199 L19.6530679,12.0883808 L15.1729321,9.04808032 L15.1729321,7.32465415 L21.1444092,11.6376386 L21.1444092,12.7070466 L15.1729321,16.5074223 L15.1729321,14.7663199 Z"/>
                </svg>
            </button>
        </li>
        <li class="richEditor-menuItem" role="presentation">
            <button class="richEditor-button" type="button" role="radio" aria-checked="false">
                <svg class="richEditorButton-icon" viewBox="0 0 24 24">
                    <title><?php echo t('Spoiler'); ?></title>
                    <path fill="currentColor" d="M11.469 15.47c-2.795-.313-4.73-3.017-4.06-5.8l4.06 5.8zM12 16.611a9.65 9.65 0 0 1-8.333-4.722 9.569 9.569 0 0 1 3.067-3.183L5.778 7.34a11.235 11.235 0 0 0-3.547 3.703 1.667 1.667 0 0 0 0 1.692A11.318 11.318 0 0 0 12 18.278c.46 0 .92-.028 1.377-.082l-1.112-1.589a9.867 9.867 0 0 1-.265.004zm9.77-3.876a11.267 11.267 0 0 1-4.985 4.496l1.67 2.387a.417.417 0 0 1-.102.58l-.72.504a.417.417 0 0 1-.58-.102L5.545 4.16a.417.417 0 0 1 .102-.58l.72-.505a.417.417 0 0 1 .58.103l1.928 2.754A11.453 11.453 0 0 1 12 5.5c4.162 0 7.812 2.222 9.77 5.543.307.522.307 1.17 0 1.692zm-1.437-.846A9.638 9.638 0 0 0 12.828 7.2a1.944 1.944 0 1 0 3.339 1.354 4.722 4.722 0 0 1-1.283 5.962l.927 1.324a9.602 9.602 0 0 0 4.522-3.952z"/>
                </svg>
            </button>
        </li>
    </ul>
</div>


<hr/>

<h2>Inline Level Formatting Menu</h2>

<div class="richEditor-menu richEditorInlineMenu" role="dialog" aria-label="<?php echo t('Inline Level Formatting Menu') ?>">
    <ul class="richEditor-menuItems" role="menubar" aria-label="<?php echo t('Inline Level Formatting Menu'); ?>">
        <li class="richEditor-menuItem" role="menuitem">
            <button class="richEditor-button" type="button">
                <svg class="spoiler-icon" viewBox="0 0 24 24">
                    <title><?php echo t('Bold'); ?></title>
                    <path d="M6.511,18v-.62a4.173,4.173,0,0,0,.845-.093.885.885,0,0,0,.736-.79,5.039,5.039,0,0,0,.063-.884V8.452a6.585,6.585,0,0,0-.047-.876,1.116,1.116,0,0,0-.194-.527.726.726,0,0,0-.4-.263,3.658,3.658,0,0,0-.674-.1v-.62h4.975a7.106,7.106,0,0,1,3.6.752A2.369,2.369,0,0,1,16.68,8.964q0,1.843-2.651,2.6v.062a4.672,4.672,0,0,1,1.542.24,3.39,3.39,0,0,1,1.171.674,3.036,3.036,0,0,1,.744,1.023,3.125,3.125,0,0,1,.263,1.287,2.49,2.49,0,0,1-.38,1.379,3.05,3.05,0,0,1-1.092.992,7.794,7.794,0,0,1-3.8.775Zm6.076-.945q2.5,0,2.5-2.248a2.3,2.3,0,0,0-.9-2.015,3.073,3.073,0,0,0-1.2-.465,9.906,9.906,0,0,0-1.806-.139h-.744v3.1a1.664,1.664,0,0,0,.5,1.364A2.659,2.659,0,0,0,12.587,17.055Zm-1.24-5.8a4.892,4.892,0,0,0,1.21-.131,2.69,2.69,0,0,0,.868-.38,1.8,1.8,0,0,0,.743-1.6,2.107,2.107,0,0,0-.557-1.635,2.645,2.645,0,0,0-1.8-.5h-1.1q-.279,0-.279.264v3.983Z" style="fill: currentColor;"/>
                </svg>
            </button>
        </li>
        <li class="richEditor-menuItem" role="menuitem">
            <button class="richEditor-button" type="button">
                <svg class="richEditorButton-icon" viewBox="0 0 24 24">
                    <title><?php echo t('Italic'); ?></title>
                    <path d="M11.472,15.4a4.381,4.381,0,0,0-.186,1.085.744.744,0,0,0,.333.713,2.323,2.323,0,0,0,1.077.186L12.51,18H7.566l.17-.62a3.8,3.8,0,0,0,.791-.07,1.282,1.282,0,0,0,.566-.271,1.62,1.62,0,0,0,.41-.558,5.534,5.534,0,0,0,.326-.93L11.642,8.7a5.332,5.332,0,0,0,.233-1.271.577.577,0,0,0-.349-.612,3.714,3.714,0,0,0-1.186-.132l.171-.62h5.038l-.171.62a3.058,3.058,0,0,0-.852.1,1.246,1.246,0,0,0-.59.38,2.578,2.578,0,0,0-.441.774,11.525,11.525,0,0,0-.4,1.287Z" style="fill: currentColor;"/>
                </svg>
            </button>
        </li>
        <li class="richEditor-menuItem" role="menuitem">
            <button class="richEditor-button" type="button">
                <svg class="richEditorButton-icon" viewBox="0 0 24 24">
                    <title><?php echo t('Strikethrough'); ?></title>
                    <path d="M12.258,13H6V12h4.2l-.05-.03a4.621,4.621,0,0,1-1.038-.805,2.531,2.531,0,0,1-.55-.892A3.285,3.285,0,0,1,8.4,9.2a3.345,3.345,0,0,1,.256-1.318,3.066,3.066,0,0,1,.721-1.046,3.242,3.242,0,0,1,1.1-.682,3.921,3.921,0,0,1,1.4-.24,3.641,3.641,0,0,1,1.271.217,4.371,4.371,0,0,1,1.194.7l.4-.7h.357l.171,3.085h-.574A3.921,3.921,0,0,0,13.611,7.32a2.484,2.484,0,0,0-1.7-.619,2.269,2.269,0,0,0-1.5.465,1.548,1.548,0,0,0-.558,1.255,1.752,1.752,0,0,0,.124.674,1.716,1.716,0,0,0,.4.574,4.034,4.034,0,0,0,.729.542,9.854,9.854,0,0,0,1.116.566,20.49,20.49,0,0,1,1.906.953q.232.135.435.27h4.6v1H15.675a2.263,2.263,0,0,1,.3.544,3.023,3.023,0,0,1,.186,1.093,3.236,3.236,0,0,1-1.177,2.541,4.014,4.014,0,0,1-1.334.721,5.393,5.393,0,0,1-1.7.256,4.773,4.773,0,0,1-1.588-.248,4.885,4.885,0,0,1-1.434-.837l-.434.76H8.132L7.9,14.358h.573a3.886,3.886,0,0,0,.411,1.255A3.215,3.215,0,0,0,10.7,17.155a3.872,3.872,0,0,0,1.294.21,2.786,2.786,0,0,0,1.813-.543,1.8,1.8,0,0,0,.667-1.473,1.752,1.752,0,0,0-.573-1.34,4.04,4.04,0,0,0-.83-.6Q12.723,13.217,12.258,13Z" style="fill: currentColor;"/>
                </svg>
            </button>
        </li>
        <li class="richEditor-menuItem" role="menuitem">
            <button class="richEditor-button" type="button">
                <svg class="richEditorButton-icon" viewBox="0 0 24 24">
                    <title><?php echo t('Paragraph Code Block'); ?></title>
                    <path fill="currentColor" fill-rule="evenodd" d="M9.11588626,16.5074223 L3.14440918,12.7070466 L3.14440918,11.6376386 L9.11588626,7.32465415 L9.11588626,9.04808032 L4.63575044,12.0883808 L9.11588626,14.7663199 L9.11588626,16.5074223 Z M14.48227,5.53936141 L11.1573124,18.4606386 L9.80043634,18.4606386 L13.131506,5.53936141 L14.48227,5.53936141 Z M15.1729321,14.7663199 L19.6530679,12.0883808 L15.1729321,9.04808032 L15.1729321,7.32465415 L21.1444092,11.6376386 L21.1444092,12.7070466 L15.1729321,16.5074223 L15.1729321,14.7663199 Z"/>
                </svg>
            </button>
        </li>
        <li class="richEditor-menuItem" role="menuitem">
            <button class="richEditor-button" type="button">
                <svg class="richEditorButton-icon" viewBox="0 0 24 24">
                    <title><?php echo t('Link'); ?></title>
                    <path d="M9.108,12.272a.731.731,0,0,0,.909.08l1.078.9a2.094,2.094,0,0,1-2.889.087l-2.4-2.019A2.089,2.089,0,0,1,5.443,8.4L6.892,6.679a2.088,2.088,0,0,1,2.942-.144l2.4,2.019a2.089,2.089,0,0,1,.362,2.924l-.1.114-1.073-.9.1-.114a.705.705,0,0,0-.192-.95l-2.4-2.019a.7.7,0,0,0-.968-.026L6.516,9.3a.7.7,0,0,0,.191.95Zm9.085,1.293a2.088,2.088,0,0,1,.362,2.924l-1.448,1.722a2.088,2.088,0,0,1-2.942.144l-2.4-2.019a2.1,2.1,0,0,1-.409-2.86l1.077.9a.73.73,0,0,0,.235.883l2.4,2.019a.7.7,0,0,0,.968.026l1.448-1.722a.7.7,0,0,0-.192-.95l-2.4-2.019a.7.7,0,0,0-.967-.026l-.1.115-1.072-.9.1-.115a2.087,2.087,0,0,1,2.942-.144ZM10.028,10.6a.466.466,0,0,1,.658-.057l3.664,3.082a.467.467,0,0,1,.057.658l-.308.366a.466.466,0,0,1-.658.057L9.776,11.626a.469.469,0,0,1-.057-.659Z" style="fill: currentColor;"/>
                </svg>
            </button>
        </li>
    </ul>
</div>

<hr/>

<h2>Link Menu</h2>
<div class="richEditor-menu insertLink" role="dialog" aria-label="<?php echo 'Insert Url'; ?>">
    <input class="InputBox insertLink-input" placeholder="Paste or type a link…">
    <a href="#" aria-label="<?php echo t('Close'); ?>" class="Close richEditor-close" role="button">
        <span>×</span>
    </a>
</div>
<hr/>

<h2>Insert Media</h2>
<div class="richEditor-menu insertMedia richEditorFlyout" style="position: relative;" role="dialog" aria-labelledby="tempId-insertMediaMenu-title" aria-describedby="tempId-insertMediaMenu-p">
    <div class="richEditorFlyout-header">
        <h2 id="tempId-insertMediaMenu-title" class="H richEditorFlyout">
            <?php echo t('Insert Media'); ?>
        </h2>
        <a href="#" aria-label="<?php echo t('Close'); ?>" class="Close richEditor-close">
            <span>×</span>
        </a>
    </div>

    <div class="richEditorFlyout-body">
        <p id="tempId-insertMediaMenu-p" class="insertMedia-description">
            <?php echo t('Paste the URL of the media you want.'); ?>
        </p>
        <input class="InputBox" placeholder="http://">
    </div>

    <div class="insertMedia-footer Footer">
        <a href="#" class="insertMedia-help" aria-label="<?php echo t('Get Help on Inserting Media'); ?>">
            <?php echo t('Help'); ?>
        </a>

        <input type="submit" class="Button Primary insertMedia-insert" value="<?php echo t('Insert'); ?>" aria-label="<?php echo t('Insert Media') ?>">
    </div>
</div>

<h2>Emoji List</h2>

<div class="richEditor-menu insertEmoji richEditorFlyout" style="position: relative; overflow: hidden" role="dialog" aria-labelledby="tempId-insertEmoji-title">
    <div class="richEditorFlyout-header">
        <h2 id="tempId-insertMediaMenu-title" class="H richEditorFlyout">
            <?php echo t('Smileys & Faces'); ?>
        </h2>
        <a href="#" aria-label="<?php echo t('Close'); ?>" class="Close richEditor-close">
            <span>×</span>
        </a>
    </div>
    <div class="richEditorFlyout-body insertEmoji-body">
        <div class="richEditor-emojis">
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
            <button class="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>


        </div>
    </div>
</div>

<div class="userContent">

    <h2>Spoiler</h2>
    <div class="spoiler isOpen">
        <button class="iconButton button-spoiler">
            <span class="spoiler-warning">
                <span class="spoiler-warningMain">
                    <svg class="spoiler-icon">
                        <title>Crossed Eye</title>
                        <path fill="currentColor" transform="translate(0 -1)" d="M6.57317675,16.3287231 C4.96911243,15.3318089 3.44472018,13.8889012 2,12 C5.05938754,8 8.47605421,6 12.25,6 C13.6612883,6 15.0226138,6.27968565 16.3339763,6.83905696 L20.6514608,2.64150254 C20.8494535,2.44900966 21.1660046,2.45346812 21.3584975,2.6514608 C21.5509903,2.84945348 21.5465319,3.16600458 21.3485392,3.35849746 L3.3485392,20.8584975 C3.15054652,21.0509903 2.83399542,21.0465319 2.64150254,20.8485392 C2.44900966,20.6505465 2.45346812,20.3339954 2.6514608,20.1415025 L6.57317675,16.3287231 L6.57317675,16.3287231 Z M15.5626154,7.58899113 C14.5016936,7.19530434 13.4103266,7 12.2871787,7 C9.03089027,7 6.04174149,8.64166208 3.28717875,12 C4.57937425,13.575433 5.92319394,14.7730857 7.3219985,15.600702 L8.69990942,14.2610664 C8.25837593,13.6178701 8,12.8391085 8,12 C8,9.790861 9.790861,8 12,8 C12.8795188,8 13.6927382,8.28386119 14.353041,8.76496625 L15.5626154,7.58899113 L15.5626154,7.58899113 Z M13.6219039,9.47579396 C13.1542626,9.17469368 12.5975322,9 12,9 C10.3431458,9 9,10.3431458 9,12 C9,12.5672928 9.15745957,13.0978089 9.43105789,13.5502276 L10.1773808,12.8246358 C10.0634411,12.573203 10,12.2940102 10,12 C10,10.8954305 10.8954305,10 12,10 C12.3140315,10 12.6111588,10.0723756 12.8756113,10.2013562 L13.6219039,9.47579396 L13.6219039,9.47579396 Z M8.44878963,17.2769193 L9.24056594,16.4926837 C10.2294317,16.8317152 11.2446131,17 12.2871787,17 C15.5434672,17 18.532616,15.3583379 21.2871787,12 C20.0256106,10.4619076 18.7148365,9.28389964 17.351729,8.45876979 L18.0612628,7.7559935 C19.6161185,8.74927417 21.0956975,10.163943 22.5,12 C19.4406125,16 16.0239458,18 12.25,18 C10.9398729,18 9.67280281,17.7589731 8.44878963,17.2769193 L8.44878963,17.2769193 Z M10.1795202,15.5626719 L10.9415164,14.8079328 C11.2706747,14.9320752 11.627405,15 12,15 C13.6568542,15 15,13.6568542 15,12 C15,11.6375376 14.9357193,11.2900888 14.8179359,10.9684315 L15.579952,10.2136728 C15.8487548,10.7513317 16,11.3580032 16,12 C16,14.209139 14.209139,16 12,16 C11.3443726,16 10.7255863,15.8422643 10.1795202,15.5626719 L10.1795202,15.5626719 Z M11.7703811,13.986962 L13.9890469,11.7894264 C13.9962879,11.8586285 14,11.9288807 14,12 C14,13.1045695 13.1045695,14 12,14 C11.9223473,14 11.8457281,13.9955745 11.7703811,13.986962 Z"></path>
                    </svg>
                    <span className="spoiler-warningLabel">{t("Spoiler Warning")}</span>
                </span>
                <span class="spoiler-chevron">
                    <svg class="spoiler-chevronUp" viewBox="0 0 20 20">
                        <title>▲</title>
                        <path fill="currentColor" stroke-linecap="square" fill-rule="evenodd" d="M6.79521339,4.1285572 L6.13258979,4.7726082 C6.04408814,4.85847112 6,4.95730046 6,5.0690962 C6,5.18057569 6.04408814,5.27940502 6.13258979,5.36526795 L11.3416605,10.4284924 L6.13275248,15.4915587 C6.04425083,15.5774216 6.00016269,15.6762509 6.00016269,15.7878885 C6.00016269,15.8995261 6.04425083,15.9983555 6.13275248,16.0842184 L6.79537608,16.7282694 C6.88371504,16.8142905 6.98539433,16.8571429 7.10025126,16.8571429 C7.21510819,16.8571429 7.31678748,16.8141323 7.40512644,16.7282694 L13.5818586,10.7248222 C13.6701976,10.6389593 13.7142857,10.54013 13.7142857,10.4284924 C13.7142857,10.3168547 13.6701976,10.2181835 13.5818586,10.1323206 L7.40512644,4.1285572 C7.31678748,4.04269427 7.21510819,4 7.10025126,4 C6.98539433,4 6.88371504,4.04269427 6.79521339,4.1285572 L6.79521339,4.1285572 Z" transform="rotate(90 9.857 10.429)"/>
                    </svg>
                    <svg class="spoiler-chevronDown" viewBox="0 0 20 20">
                        <title>▼</title>
                        <path fill="currentColor" stroke-linecap="square" fill-rule="evenodd" d="M6.79521339,4.1285572 L6.13258979,4.7726082 C6.04408814,4.85847112 6,4.95730046 6,5.0690962 C6,5.18057569 6.04408814,5.27940502 6.13258979,5.36526795 L11.3416605,10.4284924 L6.13275248,15.4915587 C6.04425083,15.5774216 6.00016269,15.6762509 6.00016269,15.7878885 C6.00016269,15.8995261 6.04425083,15.9983555 6.13275248,16.0842184 L6.79537608,16.7282694 C6.88371504,16.8142905 6.98539433,16.8571429 7.10025126,16.8571429 C7.21510819,16.8571429 7.31678748,16.8141323 7.40512644,16.7282694 L13.5818586,10.7248222 C13.6701976,10.6389593 13.7142857,10.54013 13.7142857,10.4284924 C13.7142857,10.3168547 13.6701976,10.2181835 13.5818586,10.1323206 L7.40512644,4.1285572 C7.31678748,4.04269427 7.21510819,4 7.10025126,4 C6.98539433,4 6.88371504,4.04269427 6.79521339,4.1285572 L6.79521339,4.1285572 Z" transform="rotate(-90 9.857 10.429)"/>
                    </svg>
                </span>
            </span>
        </button>
        <div class="spoiler-content">
            <p>Generating dummy emails may work for your needs, though it interferes with users notifications.</p>
            <p>Adding a +1 to gmail addresses (as in <a href="#">Myaddress+1@gmail.com</a>) will count as a unique email but notifications will go the same address, so that maybe an option for some.</p>
        </div>
    </div>

    <h2>Code Block - Inline</h2>
    <p>
        With pretty stories for which <code class="code isInline">{text code="Custom&nbsp;Text" default="Some default custom text"}</code> not a sunrise but a galaxyrise Apollonius of Perga, cosmic fugue preserve and cherish that pale blue dot muse about, a very small stage in a vast cosmic arena. Vastness is bearable only through love quasar. Ship of the imagination descended from astronomers, take root and flourish, Rig Veda colonies, astonishment. The ash of stellar alchemy rings of Uranus a very small stage in a vast cosmic arena. Gathered by gravity vanquish the impossible corpus callosum vanquish the impossible, venture hundreds of thousands, the carbon in our apple pies hundreds of thousands culture dream of the mind's eye, take root and flourish Rig Veda consciousness and billions upon billions upon billions upon billions upon billions upon billions upon billions.
    </p>

    <h2>Code Block - Paragraph</h2>
    <code class="code codeBlock">/**
 * Adds locale data to the view, and adds a respond button to the discussion page.
 */
class MyThemeNameThemeHooks extends Gdn_Plugin {

    /**
     * Fetches the current locale and sets the data for the theme view.
     * Render the locale in a smarty template using {$locale}
     *
     * @param  Controller $sender The sending controller object.
     */
    public function base_render_beforebase_render_beforebase_render_beforebase_render_beforebase_render_before($sender) {
        // Bail out if we're in the dashboard
        if (inSection('Dashboard')) {
            return;
        }

        // Fetch the currently enabled locale (en by default)
        $locale = Gdn::locale()->current();
        $sender->setData('locale', $locale);
    }
}</code>


    <h2>Blockquote</h2>
    <blockquote class="blockquote">
        <div class="blockquote-main">
            <p>
                <strong>Can we use jsConnect without providing an email address?</strong><br/>
                No. You absolutely must send an email, which is the only method for mapping users. If you are importing forum users without email addresses and need a way to map them over SSO, we recommend using dummy email addresses that follow a formula like <code class="code codeInline">uniqueID</code> <a href="#">@yoursite.com</a>.
            </p>
        </div>
    </blockquote>

    <h2>Embed - Loading</h2>
    <div class="embed" aria-live="polite">
        <div class="embedLoader">
            <div class="embedLoader-box">
                <div class="embedLoader-loader">
                    <span class="sr-only">
                        <?php echo t('Loading...'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>


    <h2>Embed - Error</h2>
    <div class="embed" role="alert">
        <ul class="embedLoader-errors">
            <li class="embedLoader-error">
                <svg class="embedLoader-icon embedLoader-warningIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M11.651,3.669,2.068,21.75H21.234Zm.884-1,10,18.865A1,1,0,0,1,21.649,23h-20a1,1,0,0,1-.884-1.468l10-18.865a1,1,0,0,1,1.768,0Zm.231,13.695H10.547L10.2,10h2.9Zm-2.535,2.354a1.24,1.24,0,0,1,.363-.952,1.493,1.493,0,0,1,1.056-.34,1.445,1.445,0,0,1,1.039.34,1.26,1.26,0,0,1,.353.952,1.223,1.223,0,0,1-.366.944A1.452,1.452,0,0,1,11.65,20a1.5,1.5,0,0,1-1.042-.34A1.206,1.206,0,0,1,10.231,18.716Z" style="fill: currentColor;"/>
                </svg>

                <span class="embedLoader-errorMessage">
                    Embed failed please try again
                </span>

                <button class="closeButton js-closeEmbedError">
                    <svg class="embedLoader-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M12,10.6293581 L5.49002397,4.11938207 C5.30046135,3.92981944 4.95620859,3.96673045 4.69799105,4.22494799 L4.22494799,4.69799105 C3.97708292,4.94585613 3.92537154,5.29601344 4.11938207,5.49002397 L10.6293581,12 L4.11938207,18.509976 C3.92981944,18.6995387 3.96673045,19.0437914 4.22494799,19.3020089 L4.69799105,19.775052 C4.94585613,20.0229171 5.29601344,20.0746285 5.49002397,19.8806179 L12,13.3706419 L18.509976,19.8806179 C18.6995387,20.0701806 19.0437914,20.0332695 19.3020089,19.775052 L19.775052,19.3020089 C20.0229171,19.0541439 20.0746285,18.7039866 19.8806179,18.509976 L13.3706419,12 L19.8806179,5.49002397 C20.0701806,5.30046135 20.0332695,4.95620859 19.775052,4.69799105 L19.3020089,4.22494799 C19.0541439,3.97708292 18.7039866,3.92537154 18.509976,4.11938207 L12,10.6293581 Z"/>
                    </svg>
                </button>
            </li>
        </ul>
    </div>

    <div class="embed" aria-live="polite">
        <ul class="embedLoader-errors">
            <li class="embedLoader-error">
                <svg class="embedLoader-icon embedLoader-warningIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M11.651,3.669,2.068,21.75H21.234Zm.884-1,10,18.865A1,1,0,0,1,21.649,23h-20a1,1,0,0,1-.884-1.468l10-18.865a1,1,0,0,1,1.768,0Zm.231,13.695H10.547L10.2,10h2.9Zm-2.535,2.354a1.24,1.24,0,0,1,.363-.952,1.493,1.493,0,0,1,1.056-.34,1.445,1.445,0,0,1,1.039.34,1.26,1.26,0,0,1,.353.952,1.223,1.223,0,0,1-.366.944A1.452,1.452,0,0,1,11.65,20a1.5,1.5,0,0,1-1.042-.34A1.206,1.206,0,0,1,10.231,18.716Z" style="fill: currentColor;"/>
                </svg>

                <span class="embedLoader-errorMessage">
                    Embed failed please try againEmbed failed please try againEmbed failed please try againEmbed failed please try againEmbed failed please try againEmbed failed please try againEmbed failed please try againEmbed failed please try againEmbed failed please try againEmbed failed please try again
                </span>

                <button class="closeButton">
                    <svg class="embedLoader-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M12,10.6293581 L5.49002397,4.11938207 C5.30046135,3.92981944 4.95620859,3.96673045 4.69799105,4.22494799 L4.22494799,4.69799105 C3.97708292,4.94585613 3.92537154,5.29601344 4.11938207,5.49002397 L10.6293581,12 L4.11938207,18.509976 C3.92981944,18.6995387 3.96673045,19.0437914 4.22494799,19.3020089 L4.69799105,19.775052 C4.94585613,20.0229171 5.29601344,20.0746285 5.49002397,19.8806179 L12,13.3706419 L18.509976,19.8806179 C18.6995387,20.0701806 19.0437914,20.0332695 19.3020089,19.775052 L19.775052,19.3020089 C20.0229171,19.0541439 20.0746285,18.7039866 19.8806179,18.509976 L13.3706419,12 L19.8806179,5.49002397 C20.0701806,5.30046135 20.0332695,4.95620859 19.775052,4.69799105 L19.3020089,4.22494799 C19.0541439,3.97708292 18.7039866,3.92537154 18.509976,4.11938207 L12,10.6293581 Z"/>
                    </svg>
                </button>
            </li>
            <li class="embedLoader-error">
                <svg class="embedLoader-icon embedLoader-warningIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M11.651,3.669,2.068,21.75H21.234Zm.884-1,10,18.865A1,1,0,0,1,21.649,23h-20a1,1,0,0,1-.884-1.468l10-18.865a1,1,0,0,1,1.768,0Zm.231,13.695H10.547L10.2,10h2.9Zm-2.535,2.354a1.24,1.24,0,0,1,.363-.952,1.493,1.493,0,0,1,1.056-.34,1.445,1.445,0,0,1,1.039.34,1.26,1.26,0,0,1,.353.952,1.223,1.223,0,0,1-.366.944A1.452,1.452,0,0,1,11.65,20a1.5,1.5,0,0,1-1.042-.34A1.206,1.206,0,0,1,10.231,18.716Z" style="fill: currentColor;"/>
                </svg>

                <span class="embedLoader-errorMessage">
                    Embed failed please try again
                </span>

                <button class="closeButton">
                    <svg class="embedLoader-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M12,10.6293581 L5.49002397,4.11938207 C5.30046135,3.92981944 4.95620859,3.96673045 4.69799105,4.22494799 L4.22494799,4.69799105 C3.97708292,4.94585613 3.92537154,5.29601344 4.11938207,5.49002397 L10.6293581,12 L4.11938207,18.509976 C3.92981944,18.6995387 3.96673045,19.0437914 4.22494799,19.3020089 L4.69799105,19.775052 C4.94585613,20.0229171 5.29601344,20.0746285 5.49002397,19.8806179 L12,13.3706419 L18.509976,19.8806179 C18.6995387,20.0701806 19.0437914,20.0332695 19.3020089,19.775052 L19.775052,19.3020089 C20.0229171,19.0541439 20.0746285,18.7039866 19.8806179,18.509976 L13.3706419,12 L19.8806179,5.49002397 C20.0701806,5.30046135 20.0332695,4.95620859 19.775052,4.69799105 L19.3020089,4.22494799 C19.0541439,3.97708292 18.7039866,3.92537154 18.509976,4.11938207 L12,10.6293581 Z"/>
                    </svg>
                </button>
            </li>
            <li class="embedLoader-error">
                <svg class="embedLoader-icon embedLoader-warningIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M11.651,3.669,2.068,21.75H21.234Zm.884-1,10,18.865A1,1,0,0,1,21.649,23h-20a1,1,0,0,1-.884-1.468l10-18.865a1,1,0,0,1,1.768,0Zm.231,13.695H10.547L10.2,10h2.9Zm-2.535,2.354a1.24,1.24,0,0,1,.363-.952,1.493,1.493,0,0,1,1.056-.34,1.445,1.445,0,0,1,1.039.34,1.26,1.26,0,0,1,.353.952,1.223,1.223,0,0,1-.366.944A1.452,1.452,0,0,1,11.65,20a1.5,1.5,0,0,1-1.042-.34A1.206,1.206,0,0,1,10.231,18.716Z" style="fill: currentColor;"/>
                </svg>

                <span class="embedLoader-errorMessage">
                    EmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagainEmbedfailedpleasetryagain
                </span>

                <button class="closeButton">
                    <svg class="embedLoader-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M12,10.6293581 L5.49002397,4.11938207 C5.30046135,3.92981944 4.95620859,3.96673045 4.69799105,4.22494799 L4.22494799,4.69799105 C3.97708292,4.94585613 3.92537154,5.29601344 4.11938207,5.49002397 L10.6293581,12 L4.11938207,18.509976 C3.92981944,18.6995387 3.96673045,19.0437914 4.22494799,19.3020089 L4.69799105,19.775052 C4.94585613,20.0229171 5.29601344,20.0746285 5.49002397,19.8806179 L12,13.3706419 L18.509976,19.8806179 C18.6995387,20.0701806 19.0437914,20.0332695 19.3020089,19.775052 L19.775052,19.3020089 C20.0229171,19.0541439 20.0746285,18.7039866 19.8806179,18.509976 L13.3706419,12 L19.8806179,5.49002397 C20.0701806,5.30046135 20.0332695,4.95620859 19.775052,4.69799105 L19.3020089,4.22494799 C19.0541439,3.97708292 18.7039866,3.92537154 18.509976,4.11938207 L12,10.6293581 Z"/>
                    </svg>
                </button>
            </li>
        </ul>
    </div>


    <h2>Embed - Image</h2>
    <div class="embedImage">
        <img class="embedImage-img" src="https://images.pexels.com/photos/31459/pexels-photo.jpg?w=1260&h=750&dpr=2&auto=compress&cs=tinysrgb" alt="Some Alt Text">
    </div>


    <h2>Embed - Image ~ Small</h2>
    <div class="embedImage">
        <img class="embedImage-img" src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/81/Wikimedia-logo.svg/45px-Wikimedia-logo.svg.png" alt="Some Alt Text">
    </div>


    <h2>Embed - Image ~ Wide</h2>
    <div class="embedImage">
        <img class="embedImage-img" src="https://upload.wikimedia.org/wikipedia/commons/0/0a/%28PANORAMA%29_Just_a_Rainbow_%286696824735%29.jpg" alt="Some Alt Text">
    </div>

    <h2>Embed - Image ~ Tall</h2>
    <div class="embedImage">
        <img class="embedImage-img" src="https://upload.wikimedia.org/wikipedia/commons/e/e7/Mycket_h%C3%B6ga_tallar_i_sluttningen_mellan_Skatberget_och_Finnboda.jpg" alt="Some Alt Text">
    </div>

    <h2>Embed - Video Placeholder</h2>
    <div class="embedVideo" aria-label="Video title">
        <div class="embedVideo-ratio is16by9">
            <button class="embedVideo-playButton js-playVideo" style="background-image: url('https://images.pexels.com/photos/31459/pexels-photo.jpg?w=1260&h=750&dpr=2&auto=compress&cs=tinysrgb')">
                <svg class="embedVideo-playIcon" xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 24 24">
                    <title>Play Video</title>
                    <path class="embedVideo-playIconPath embedVideo-playIconPath-circle" style="fill: currentColor; stroke-width: .3;" d="M11,0A11,11,0,1,0,22,11,11,11,0,0,0,11,0Zm0,20.308A9.308,9.308,0,1,1,20.308,11,9.308,9.308,0,0,1,11,20.308Z"/>
                    <polygon class="embedVideo-playIconPath embedVideo-playIconPath-triangle" style="fill: currentColor; stroke-width: .3;" points="8.609 6.696 8.609 15.304 16.261 11 8.609 6.696"/>
                </svg>
            </button>
        </div>
    </div>

    <h2>Embed - Video Standard Ratio (default rations include: 21:9, 16:9, 4:3, 1:1) Notice we get a custom class for the ratio </h2>
    <div class="embedVideo">
        <div class="embedVideo-ratio is16by9">
            <iframe class="embedVideo-iframe" src="https://www.youtube.com/embed/zpOULjyy-n8?rel=0" allowfullscreen></iframe>
        </div>
    </div>

    <h2>Embed - Video Calculated Ratio (default rations include: 21:9, 16:9, 4:3, 1:1. If no standard ratio is found, set padding-top with inline styles on embedVideo element. I'm using 16:9 as an example here, but the technique is the same no matter the ratio. Divide width / height - example: 9/16 gives 56.25%)</h2>
    <div class="embedVideo">
        <div class="embedVideo-ratio" style="padding-top: 56.25%;">
            <iframe class="embedVideo-iframe" src="https://www.youtube.com/embed/zpOULjyy-n8?rel=0" allowfullscreen></iframe>
        </div>
    </div>

    <h2>Embed - Internal URL</h2>
    <a href="#" class="embedLink">
        <article class="embedLink-body">
            <div class="embedLink-main">
                <div class="embedLink-header">
                    <h3 class="embedLink-title">
                        Hulk attacks New York, kills 17, injures 23 in deadliest attack in 5 years   Hulk attacks New York, kills 17, injures 23 in deadliest attack in 5 years
                    </h3>
                    <span class="embedLink-userPhoto PhotoWrap">
                        <img src="https://secure.gravatar.com/avatar/b0420af06d6fecc16fc88a88cbea8218/?default=https%3A%2F%2Fvanillicon.com%2Fb0420af06d6fecc16fc88a88cbea8218_200.png&amp;rating=g&amp;size=120" alt="Linc" class="ProfilePhoto ProfilePhotoMedium">
                    </span>
                    <span class="embedLink-userName">steve_captain_rogers</span>
                    <time class="embedLink-dateTime metaStyle" datetime="2017-02-17 11:13">Feb 17, 2017 11:13 AM</time>
                </div>
                <div class="embedLink-excerpt">
                    The Battle of New York, locally known as "The Incident", was a major battle between the Avengers and Loki with his borrowed Chitauri army in Manhattan, New York City. It was, according to Loki's plan, the first battle in Loki's war to subjugate Earth, but the nd Loki with his borrowed…
                </div>
            </div>
        </article>
    </a>

    <h2>Embed - External URL ~ With Image</h2>
    <a href="#" class="embedLink">
        <article class="embedLink-body">
            <div class="embedLink-image" aria-hidden="true" style="background-image: url(https://cdn.mdn.mozilla.net/static/img/opengraph-logo.72382e605ce3.png)">

            </div>
            <div class="embedLink-main">
                <div class="embedLink-header">
                    <h3 class="embedLink-title">
                        Hulk attacks New York, kills 17, injures 23 in deadliest attack in 5 years   Hulk attacks New York, kills 17, injures 23 in deadliest attack in 5 years
                    </h3>
                    <span class="embedLink-source metaStyle">
                        nytimes.com
                    </span>
                </div>
                <div class="embedLink-excerpt">
                    The Battle of New York, locally known as "The Incident",
                    was a major battle between the Avengers and Loki with his borrowed Chitauri army in Manhattan, New York City. It was, according to Loki's plan, the first battle in Loki's war to subjugate Earth, but the actions of the Avengers neutralized the threat of the Chitauri before they could continue the invasion…
                </div>
            </div>
        </article>
    </a>

    <h2>Embed - External URL ~ Without Image</h2>
    <a href="#" class="embedLink">
        <article class="embedLink-body">
            <div class="embedLink-main">
                <div class="embedLink-header">
                    <h3 class="embedLink-title">
                        Hulk attacks New York, kills 17, injures 23 in deadliest attack in 5 years   Hulk attacks New York, kills 17, injures 23 in deadliest attack in 5 years
                    </h3>
                    <span class="embedLink-source metaStyle">
                        nytimes.com
                    </span>
                </div>
                <div class="embedLink-excerpt">
                    The Battle of New York, locally known as "The Incident", was a major battle between the Avengers and Loki with his borrowed Chitauri army in Manhattan, New York City. It was, according to Loki's plan, the first battle in Loki's war to subjugate Earth, but the actions of the Avengers neutralized the threat of the Chitauri before they could continue the invasion…
                </div>
            </div>
        </article>
    </a>
</div>
