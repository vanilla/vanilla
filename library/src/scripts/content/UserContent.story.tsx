/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import UserContent from "@library/content/UserContent";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";

storiesOf("User Content", module)
    .add("Inline", () => {
        const content = `
    <h2 data-id="inline-operations">Inline operations</h2><p>Quasar rich in mystery Apollonius of Perga concept of the number one rich in mystery! Apollonius of Perga, rogue, hearts of the stars, brain is the seed of intelligence dispassionate extraterrestrial observer finite but unbounded. Tingling of the spine kindling the energy hidden in matter gathered by gravity science Apollonius of Perga Euclid cosmic fugue gathered by gravity take root and flourish dream of the mind's eye descended from astronomers ship of the imagination vastness is bearable only through love with pretty stories for which there's little good evidence Orion's sword. Trillion a billion trillion Apollonius of Perga, not a sunrise but a galaxy rise the sky calls to us! Descended from astronomers?</p><p>Some Text Here. <code class="code codeInline" spellcheck="false">Code Inline</code> Some More Text</p><p><strong>Bold</strong></p><p><em>italic</em></p><p><strong><em>bold italic</em></strong></p><p><strong><em><s>bold italic strike</s></em></strong></p><p><a href="http://test.com/" rel="nofollow"><strong><em><s>bold italic strike link</s></em></strong></a></p><p>Some text with a mention in it&nbsp;<a class="atMention" data-username="Alex Other Name" data-userid="23" href="http://dev.vanilla.localhost/profile/Alex%20Other%20Name">@Alex Other Name</a>&nbsp;Another mention&nbsp;<a class="atMention" data-username="System" data-userid="1" href="http://dev.vanilla.localhost/profile/System">@System</a>.</p><p>Some text with emojis🤗🤔🤣.</p>`;

        return (
            <StoryContent>
                <UserContent content={content} />{" "}
            </StoryContent>
        );
    })
    .add("Paragraph Formatting", () => {
        const content = `
        <h1>h1</h1>
        <h2>h2</h2>
        <h3>h3</h3>
        <h4>h4</h4>
        <h5>h5</h5>
        <h6>h6</h6>
        <pre class="code codeBlock" spellcheck="false">/**
        *adds locale data to the view, and adds a respond button to the discussion page.
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
               $locale = Gdn::locale()-&gt;current();
               $sender-&gt;setData('locale', $locale);
           }
       }
       </pre><p><br></p><h2>H2 Here. Spoiler next</h2><div class="spoiler"><div contenteditable="false" class="spoiler-buttonContainer">
       <button title="Toggle Spoiler" class="iconButton button-spoiler js-toggleSpoiler">
           <span class="spoiler-warning">
               <span class="spoiler-warningMain">
                   <svg class="icon spoiler-icon" viewBox="0 0 24 24">
                       <title>Spoiler</title>
                       <path d="M8.138,16.569l.606-.606a6.677,6.677,0,0,0,1.108.562,5.952,5.952,0,0,0,2.674.393,7.935,7.935,0,0,0,1.008-.2,11.556,11.556,0,0,0,5.7-4.641.286.286,0,0,0-.02-.345c-.039-.05-.077-.123-.116-.173a14.572,14.572,0,0,0-2.917-3.035l.6-.6a15.062,15.062,0,0,1,2.857,3.028,1.62,1.62,0,0,0,.154.245,1.518,1.518,0,0,1,.02,1.5,12.245,12.245,0,0,1-6.065,4.911,6.307,6.307,0,0,1-1.106.22,4.518,4.518,0,0,1-.581.025,6.655,6.655,0,0,1-2.383-.466A8.023,8.023,0,0,1,8.138,16.569Zm-.824-.59a14.661,14.661,0,0,1-2.965-3.112,1.424,1.424,0,0,1,0-1.867A13.69,13.69,0,0,1,8.863,6.851a6.31,6.31,0,0,1,6.532.123c.191.112.381.231.568.356l-.621.621c-.092-.058-.184-.114-.277-.168a5.945,5.945,0,0,0-3.081-.909,6.007,6.007,0,0,0-2.868.786,13.127,13.127,0,0,0-4.263,3.929c-.214.271-.214.343,0,.639a13.845,13.845,0,0,0,3.059,3.153ZM13.9,9.4l-.618.618a2.542,2.542,0,0,0-3.475,3.475l-.61.61A3.381,3.381,0,0,1,12,8.822,3.4,3.4,0,0,1,13.9,9.4Zm.74.674a3.3,3.3,0,0,1,.748,2.138,3.382,3.382,0,0,1-5.515,2.629l.6-.6a2.542,2.542,0,0,0,3.559-3.559Zm-3.146,3.146L13.008,11.7a1.129,1.129,0,0,1-1.516,1.516Zm-.6-.811a1.061,1.061,0,0,1-.018-.2A1.129,1.129,0,0,1,12,11.079a1.164,1.164,0,0,1,.2.017Z" style="currentColor"></path>
                       <polygon points="19.146 4.146 19.854 4.854 4.854 19.854 4.146 19.146 19.146 4.146" style="currentColor"></polygon>
                   </svg>
                   <strong class="spoiler-warningBefore">
                       Spoiler Warning
                   </strong>
               </span>
               <span class="spoiler-chevron">
           <svg class="spoiler-chevronUp" viewBox="0 0 20 20">
               <title>▲</title>
               <path fill="currentColor" stroke-linecap="square" fillRule="evenodd" d="M6.79521339,4.1285572 L6.13258979,4.7726082 C6.04408814,4.85847112 6,4.95730046 6,5.0690962 C6,5.18057569 6.04408814,5.27940502 6.13258979,5.36526795 L11.3416605,10.4284924 L6.13275248,15.4915587 C6.04425083,15.5774216 6.00016269,15.6762509 6.00016269,15.7878885 C6.00016269,15.8995261 6.04425083,15.9983555 6.13275248,16.0842184 L6.79537608,16.7282694 C6.88371504,16.8142905 6.98539433,16.8571429 7.10025126,16.8571429 C7.21510819,16.8571429 7.31678748,16.8141323 7.40512644,16.7282694 L13.5818586,10.7248222 C13.6701976,10.6389593 13.7142857,10.54013 13.7142857,10.4284924 C13.7142857,10.3168547 13.6701976,10.2181835 13.5818586,10.1323206 L7.40512644,4.1285572 C7.31678748,4.04269427 7.21510819,4 7.10025126,4 C6.98539433,4 6.88371504,4.04269427 6.79521339,4.1285572 L6.79521339,4.1285572 Z" transform="rotate(-90 9.857 10.429)"></path>
           </svg>
           <svg class="spoiler-chevronDown" viewBox="0 0 20 20">
               <title>▼</title>
               <path fill="currentColor" stroke-linecap="square" fillRule="evenodd" d="M6.79521339,4.1285572 L6.13258979,4.7726082 C6.04408814,4.85847112 6,4.95730046 6,5.0690962 C6,5.18057569 6.04408814,5.27940502 6.13258979,5.36526795 L11.3416605,10.4284924 L6.13275248,15.4915587 C6.04425083,15.5774216 6.00016269,15.6762509 6.00016269,15.7878885 C6.00016269,15.8995261 6.04425083,15.9983555 6.13275248,16.0842184 L6.79537608,16.7282694 C6.88371504,16.8142905 6.98539433,16.8571429 7.10025126,16.8571429 C7.21510819,16.8571429 7.31678748,16.8141323 7.40512644,16.7282694 L13.5818586,10.7248222 C13.6701976,10.6389593 13.7142857,10.54013 13.7142857,10.4284924 C13.7142857,10.3168547 13.6701976,10.2181835 13.5818586,10.1323206 L7.40512644,4.1285572 C7.31678748,4.04269427 7.21510819,4 7.10025126,4 C6.98539433,4 6.88371504,4.04269427 6.79521339,4.1285572 L6.79521339,4.1285572 Z" transform="rotate(90 9.857 10.429)"></path>
           </svg>
       </span>
           </span>
       </button></div><div class="spoiler-content"><p class="spoiler-line">Some Spoiler content with formatting <strong>bold</strong> <em>italic </em><s>strike</s></p><p class="spoiler-line"><br></p><p class="spoiler-line"><br></p><p></p><p class="spoiler-line">Newlines above <a href="unsafe:test link" rel="nofollow">Link</a></p><p class="spoiler-line">Another line</p></div></div><p><br></p><p>A blockquote will be next.</p><p><br></p><p></p><div class="blockquote"><div class="blockquote-content"><p class="blockquote-line">Some Block quote content<strong>bold</strong> <em>italic </em><s>strike</s></p><p class="blockquote-line"><s>More blockquote content</s></p></div></div><p></p><p><br></p><p></p><p>Unordered List</p><ul><li>Line 1</li><li>Line 2 (2 empty list items after this)</li><li><br></li><li><br></li><li>Line 5 item with <strong>bold and a </strong><a href="https://vanillaforums.com" rel="nofollow"><strong>link</strong></a><strong>.</strong></li><li>Line 6 item with an emoji<span class="safeEmoji nativeEmoji">😉</span>.</li></ul><p>Ordered List</p><ol><li>Number 1</li><li>Number 2</li><li>Number 3 (Empty line below)</li><li><br></li><li>Number 5 with <strong>bold and a </strong><a href="https://vanillaforums.com/" rel="nofollow"><strong>link</strong></a><strong>.</strong></li></ol><p><br></p>`;

        return (
            <StoryContent>
                <UserContent content={content} />{" "}
            </StoryContent>
        );
    });
