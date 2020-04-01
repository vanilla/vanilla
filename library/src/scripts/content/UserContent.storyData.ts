/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export const STORY_CONTENT_RICH = `
<h1>h1</h1>
<h2>h2</h2>
<h3>h3</h3>
<h4>h4</h4>
<h5>h5</h5>
<h6>h6</h6>
<pre class="code codeBlock" spellcheck="false">
/**
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
</pre>
<h2>H2 Here. Spoiler next</h2>
<div class="spoiler">
 <div contenteditable="false" class="spoiler-buttonContainer">
   <button title="Toggle Spoiler" class="iconButton button-spoiler js-toggleSpoiler">
       <span class="spoiler-warning">
           <span class="spoiler-warningMain">
               <svg class="spoiler-icon" viewBox="0 0 24 24">
                   <title>Spoiler</title>
                   <path fill="currentColor" d="M11.469 15.47c-2.795-.313-4.73-3.017-4.06-5.8l4.06 5.8zM12 16.611a9.65 9.65 0 0 1-8.333-4.722 9.569 9.569 0 0 1 3.067-3.183L5.778 7.34a11.235 11.235 0 0 0-3.547 3.703 1.667 1.667 0 0 0 0 1.692A11.318 11.318 0 0 0 12 18.278c.46 0 .92-.028 1.377-.082l-1.112-1.589a9.867 9.867 0 0 1-.265.004zm9.77-3.876a11.267 11.267 0 0 1-4.985 4.496l1.67 2.387a.417.417 0 0 1-.102.58l-.72.504a.417.417 0 0 1-.58-.102L5.545 4.16a.417.417 0 0 1 .102-.58l.72-.505a.417.417 0 0 1 .58.103l1.928 2.754A11.453 11.453 0 0 1 12 5.5c4.162 0 7.812 2.222 9.77 5.543.307.522.307 1.17 0 1.692zm-1.437-.846A9.638 9.638 0 0 0 12.828 7.2a1.944 1.944 0 1 0 3.339 1.354 4.722 4.722 0 0 1-1.283 5.962l.927 1.324a9.602 9.602 0 0 0 4.522-3.952z"/>
               </svg>
               <span class="spoiler-warningLabel">
                   Spoiler Warning
               </span>
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
   </button>
</div>
<div class="spoiler-content">
 <p class="spoiler-line">Some Spoiler content with formatting <strong>bold</strong> <em>italic </em><s>strike</s></p><p class="spoiler-line"><br></p><p class="spoiler-line"><br></p><p></p><p class="spoiler-line">Newlines above <a href="unsafe:test link" rel="nofollow">Link</a></p><p class="spoiler-line">Another line</p>
</div>
</div>
<h2>Block quote</h2>
<div class="blockquote"><div class="blockquote-content"><p class="blockquote-line">asfasdfadsfa</p></div></div>

<div class="embedExternal embedImage">
  <div class="embedExternal-content">
      <a class="embedImage-link" href="https://us.v-cdn.net/5022541/uploads/293/WYDAXHVB5VP4.png" rel="nofollow noreferrer noopener ugc" target="_blank">
         <img class="embedImage-img" src="https://us.v-cdn.net/5022541/uploads/293/WYDAXHVB5VP4.png" alt="image.png">
      </a>
  </div>
</div>

<div class="embedExternal embedImage">
  <div class="embedExternal-content">
      <a class="embedImage-link" href="https://us.v-cdn.net/5022541/uploads/605/ZWG1GNJIG7JL.png" rel="nofollow noreferrer noopener ugc" target="_blank">
         <img class="embedImage-img" src="https://us.v-cdn.net/5022541/uploads/605/ZWG1GNJIG7JL.png" alt="image.png">
      </a>
  </div>
</div>

<div class="embedExternal embedImage">
  <div class="embedExternal-content">
     <a class="embedImage-link" href="https://us.v-cdn.net/5022541/uploads/382/CIQR7QWIU422.jpg" rel="nofollow noreferrer noopener ugc" target="_blank">
        <img class="embedImage-img" src="https://us.v-cdn.net/5022541/uploads/382/CIQR7QWIU422.jpg" alt="Untitled Image">
     </a>
  </div>
</div>

<h2>Inline operations</h2>
<p>
 Quasar rich in mystery Apollonius of Perga concept of the number one rich in mystery! Apollonius of Perga, rogue, hearts of the stars, brain is the seed of intelligence dispassionate extraterrestrial observer finite but unbounded. Tingling of the spine kindling the energy hidden in matter gathered by gravity science Apollonius of Perga Euclid cosmic fugue gathered by gravity take root and flourish dream of the mind's eye descended from astronomers ship of the imagination vastness is bearable only through love with pretty stories for which there's little good evidence Orion's sword. Trillion a billion trillion Apollonius of Perga, not a sunrise but a galaxy rise the sky calls to us! Descended from astronomers?</p><p>Some Text Here. <code class="code codeInline" spellcheck="false">Code Inline</code> Some More Text
</p>
<p>
 <strong>Bold</strong></p><p><em>italic</em></p><p><strong><em>bold italic</em></strong></p><p><strong><em><s>bold italic strike</s></em></strong>
</p>
<p>
 <a href="http://test.com/" rel="nofollow"><strong><em><s>bold italic strike link</s></em></strong></a>
</p>
<p>
 Some text with a mention in it&nbsp;<a class="atMention" data-username="Alex Other Name" data-userid="23" href="http://dev.vanilla.localhost/profile/Alex%20Other%20Name">@Alex Other Name</a>&nbsp;Another mention&nbsp;<a class="atMention" data-username="System" data-userid="1" href="http://dev.vanilla.localhost/profile/System">@System</a>.
</p>
<p>
 Some text with emojis🤗🤔🤣.
</p>
<p>A blockquote will be next.</p>
<div class="blockquote"><div class="blockquote-content"><p class="blockquote-line">Some Block quote content with formatting&nbsp;<strong>bold</strong>&nbsp;<em>italic&nbsp;</em><s>strike</s></p></div></div>
<p>Unordered List</p>
<ul>
 <li>Line 1</li>
 <li>Line 2 (2 empty list items after this)</li>
 <li>Line 5 item with <strong>bold and a </strong>
   <a href="https://vanillaforums.com" rel="nofollow"><strong>link</strong></a><strong>.</strong>
 </li>
 <li>Line 6 item with an emoji<span class="safeEmoji nativeEmoji">😉</span>.</li>
 <li>
    Nested List
    <ul>
     <li>Line 1</li>
     <li>Line 2 (2 empty list items after this)</li>
     <li>Line 5 item with <strong>bold and a </strong>
       <a href="https://vanillaforums.com" rel="nofollow"><strong>link</strong></a><strong>.</strong>
     </li>
     <li>
       Nested List
     <ul>
         <li>Line 1</li>
         <li>Line 2 (2 empty list items after this)</li>
         <li>Line 5 item with <strong>bold and a </strong>
           <a href="https://vanillaforums.com" rel="nofollow"><strong>link</strong></a><strong>.</strong>
         </li>
         <li>Line 6 item with an emoji<span class="safeEmoji nativeEmoji">😉</span>.</li>
       </ul>
     </li>
   </ul>
</li>
</ul>

<p>Ordered List</p>
<ol>
 <li>Number 1</li>
 <li>
    Nested
    <ol>
     <li>Number 1</li>
     <li>Number 2</li>
      <li>
        Nested
        <ol>
         <li>Number 1</li>
         <li>Number 2</li>
          <li>
            Nested
            <ol>
             <li>Number 1</li>
             <li>Number 2</li>
           </ol>
         </li>
       </ol>
     </li>
   </ol>
 </li>
 <li>Number 3 (Empty line below)</li>
 <li><br/></li>
 <li>Number 5 with <strong>bold and a </strong>
   <a href="https://vanillaforums.com/" rel="nofollow"><strong>link</strong></a><strong>.</strong>
 </li>
</ol>
`;

export const STORY_CONTENT_TABLES = `
<p>This is a table with main headings</p>
<table>
    <thead>
      <th>Company</th>
      <th>Contact</th>
      <th>Country</th>
    </thead>
    <tbody>
        <tr>
            <td>Alfreds Futterkiste</td>
            <td>Maria Anders</td>
            <td>Germany</td>
        </tr>
        <tr class="alt">
            <td>Berglunds snabbköp</td>
            <td>Christina Berglund</td>
            <td>Sweden</td>
        </tr>
        <tr>
            <td>Centro comercial Moctezuma</td>
            <td>Francisco Chang</td>
            <td>Mexico</td>
        </tr>
        <tr class="alt">
            <td>Ernst Handel</td>
            <td>Roland Mendel</td>
            <td>Austria</td>
        </tr>
        <tr>
            <td>Island Trading</td>
            <td>Helen Bennett</td>
            <td>UK</td>
        </tr>
        <tr class="alt">
            <td>Königlich Essen</td>
            <td>Philip Cramer</td>
            <td>Germany</td>
        </tr>
        <tr>
            <td>Laughing Bacchus Winecellars</td>
            <td>Yoshi Tannamuri</td>
            <td>Canada</td>
        </tr>
        <tr class="alt">
            <td>Magazzini Alimentari Riuniti</td>
            <td>Giovanni Rovelli</td>
            <td>Italy</td>
        </tr>
    </tbody>
</table>
<p>This is a table with side headings</p>
<table>
    <tbody>
        <tr>
            <th>#</th>
            <th>Company</th>
            <th>Contact</th>
            <th>Country</th>
        </tr>
        <tr>
            <th>1</th>
            <td>Alfreds Futterkiste</td>
            <td>Maria Anders</td>
            <td>Germany</td>
        </tr>
        <tr>
            <th>2</th>
            <td>Berglunds snabbköp</td>
            <td>Christina Berglund</td>
            <td>Sweden</td>
        </tr>
        <tr>
            <th>3</th>
            <td>Centro comercial Moctezuma</td>
            <td>Francisco Chang</td>
            <td>Mexico</td>
        </tr>
        <tr>
            <th>4</th>
            <td>Ernst Handel</td>
            <td>Roland Mendel</td>
            <td>Austria</td>
        </tr>
        <tr>
            <th>5</th>
            <td>Island Trading</td>
            <td>Helen Bennett</td>
            <td>UK</td>
        </tr>
        <tr>
            <th>6</th>
            <td>Königlich Essen</td>
            <td>Philip Cramer</td>
            <td>Germany</td>
        </tr>
        <tr>
            <th>7</th>
            <td>Laughing Bacchus Winecellars</td>
            <td>Yoshi Tannamuri</td>
            <td>Canada</td>
        </tr>
        <tr>
            <th>8</th>
            <td>Magazzini Alimentari Riuniti</td>
            <td>Giovanni Rovelli</td>
            <td>Italy</td>
        </tr>
    </tbody>
</table>
<p>This table has no headings</p>
<table>
    <tbody>
        <tr>
            <td>Alfreds Futterkiste</td>
            <td>Maria Anders</td>
            <td>Germany</td>
        </tr>
        <tr>
            <td>Berglunds snabbköp</td>
            <td>Christina Berglund</td>
            <td>Sweden</td>
        </tr>
        <tr>
            <td>Centro comercial Moctezuma</td>
            <td>Francisco Chang</td>
            <td>Mexico</td>
        </tr>
        <tr>
            <td>Ernst Handel</td>
            <td>Roland Mendel</td>
            <td>Austria</td>
        </tr>
    </tbody>
</table>
`;

export const STORY_CONTENT_LEGACY = `
<h2>Legacy Code - BB Code</h2>

<div class="bbcode_left">
    <img src="https://us.v-cdn.net/5022541/uploads/166/54V2AXRD4C0R.jpg" alt="" class="embedImage-img"/>
</div>

<div class="bbcode_center">
    <img src="https://us.v-cdn.net/5022541/uploads/166/54V2AXRD4C0R.jpg" alt="" class="embedImage-img"/>
</div>

<div class="bbcode_right">
    <img src="https://us.v-cdn.net/5022541/uploads/166/54V2AXRD4C0R.jpg" alt="" class="embedImage-img"/>
</div>

<h2>Legacy Code - Quote</h2>

<blockquote class="Quote UserQuote blockquote">
    <div class="blockquote-content">
        <a rel="nofollow" href="#">fakeUser</a> wrote: <a rel="nofollow" href="/en-hutch/discussion/comment/41906549#Comment_41906549" class="QuoteLink">»</a>
    </div>
    <div class="blockquote-content">
        <blockquote class="Quote UserQuote blockquote">
            <div class="blockquote-content">
                <a rel="nofollow" href="#">fakeUser</a> wrote: <a rel="nofollow" href="/en-hutch/discussion/231710" class="QuoteLink">»</a>
            </div>
            <div class="blockquote-content">Looks like quote trees aren't working properly, and the little indicator that a thread has been read is no longer functional.  Both Firefox and Chrome desktop.</div>
        </blockquote>
        <br>
        Just as an example
    </div>
</blockquote>

<h3>Reasonable nesting</h3>

<blockquote class="Quote UserQuote blockquote" style="font-size: 15px;">
    <div class="blockquote-content"><a rel="nofollow" href="/profile/Monaogg">Monaogg</a> wrote: <a rel="nofollow" href="/discussion/comment/95227532#Comment_95227532" class="QuoteLink">»</a></div>
    <div class="blockquote-content">The following is how AWFUL the new look can get with nested quotes showing up.<br><blockquote class="Quote UserQuote blockquote" style="font-size: 15px;">
    <div class="blockquote-content"><a rel="nofollow" href="/profile/charger21">charger21</a> wrote: <a rel="nofollow" href="/discussion/comment/95216356#Comment_95216356" class="QuoteLink">»</a></div>
    <div class="blockquote-content">
    <blockquote class="Quote UserQuote blockquote" style="font-size: 15px;">
    <div class="blockquote-content"><a rel="nofollow" href="/profile/pie-eyed">pie-eyed</a> wrote: <a rel="nofollow" href="/discussion/comment/95216205#Comment_95216205" class="QuoteLink">»</a></div>
    <div class="blockquote-content">
    <blockquote class="Quote UserQuote blockquote" style="font-size: 15px;">
    <div class="blockquote-content"><a rel="nofollow" href="/profile/charger21">charger21</a> wrote: <a rel="nofollow" href="/discussion/comment/95215578#Comment_95215578" class="QuoteLink">»</a></div>
    <div class="blockquote-content">
    <blockquote class="Quote UserQuote blockquote" style="font-size: 15px;">
    <div class="blockquote-content"><a rel="nofollow" href="/profile/pie-eyed">pie-eyed</a> wrote: <a rel="nofollow" href="/discussion/comment/95215026#Comment_95215026" class="QuoteLink">»</a></div>
    <div class="blockquote-content">
    <blockquote class="Quote UserQuote blockquote" style="font-size: 15px;">
    <div class="blockquote-content"><a rel="nofollow" href="/profile/charger21">charger21</a> wrote: <a rel="nofollow" href="/discussion/comment/95213747#Comment_95213747" class="QuoteLink">»</a></div>
    <div class="blockquote-content">
    <blockquote class="Quote UserQuote blockquote" style="font-size: 15px;">
    <div class="blockquote-content"><a rel="nofollow" href="/profile/EveT1991">EveT1991</a> wrote: <a rel="nofollow" href="/discussion/comment/95213690#Comment_95213690" class="QuoteLink">»</a></div>
    <div class="blockquote-content">
    <blockquote class="Quote UserQuote blockquote" style="font-size: 15px;">
    <div class="blockquote-content"><a rel="nofollow" href="/profile/hamsters1">hamsters1</a> wrote: <a rel="nofollow" href="/discussion/comment/95213428#Comment_95213428" class="QuoteLink">»</a></div>
    <div class="blockquote-content">
    <blockquote class="Quote UserQuote blockquote" style="font-size: 15px;">
    <div class="blockquote-content"><a rel="nofollow" href="/profile/EveT1991">EveT1991</a> wrote: <a rel="nofollow" href="/discussion/comment/95213347#Comment_95213347" class="QuoteLink">»</a></div>
    <div class="blockquote-content">
    <blockquote class="Quote UserQuote blockquote" style="font-size: 15px;">
    <div class="blockquote-content"><a rel="nofollow" href="/profile/Straker">Straker</a> wrote: <a rel="nofollow" href="/discussion/comment/95212523#Comment_95212523" class="QuoteLink">»</a></div>
    <div class="blockquote-content">
    <blockquote class="Quote UserQuote blockquote" style="font-size: 15px;">
    <div class="blockquote-content"><a rel="nofollow" href="/profile/EveT1991">EveT1991</a> wrote: <a rel="nofollow" href="/discussion/comment/95212246#Comment_95212246" class="QuoteLink">»</a></div>
    <div class="blockquote-content">
    <blockquote class="Quote UserQuote blockquote" style="font-size: 15px;">
    <div class="blockquote-content"><a rel="nofollow" href="/profile/andykn">andykn</a> wrote: <a rel="nofollow" href="/discussion/comment/95212232#Comment_95212232" class="QuoteLink">»</a></div>
    <div class="blockquote-content">
    <blockquote class="Quote UserQuote blockquote" style="font-size: 15px;">
    <div class="blockquote-content"><a rel="nofollow" href="/profile/EveT1991">EveT1991</a> wrote: <a rel="nofollow" href="/discussion/comment/95212228#Comment_95212228" class="QuoteLink">»</a></div>
    <div class="blockquote-content">
    <blockquote class="Quote UserQuote blockquote" style="font-size: 15px;">
    <div class="blockquote-content"><a rel="nofollow" href="/profile/Monaogg">Monaogg</a> wrote: <a rel="nofollow" href="/discussion/comment/95212175#Comment_95212175" class="QuoteLink">»</a></div>
    <div class="blockquote-content">
    <blockquote class="Quote UserQuote blockquote" style="font-size: 15px;">
    <div class="blockquote-content"><a rel="nofollow" href="/profile/EveT1991">EveT1991</a> wrote: <a rel="nofollow" href="/discussion/comment/95211995#Comment_95211995" class="QuoteLink">»</a></div>
    <div class="blockquote-content">One of my older brothers who is the middle child voted to remain but me, my younger brother , one of my older brothers, my older sister voted to leave the EU. So I respect and accept why people voted to remain in the EU. It would be really boring if everyone voted remain.</div>
    </blockquote>
    <br>
    No excuse for destroying the economy, the NHS, food standards, £, GFA or the UKs standing in the world.</div>
    </blockquote>
    <br>
    I voted to leave the EU as did many other people why are people ganging up on me 😭.</div>
    </blockquote>
    <br>
    Because what you are saying isn't true.</div>
    </blockquote>
    <br>
    I want us to leave the EU. How do you know it's not true? We pay alot of money to the EU</div>
    </blockquote>
    <br>
    £10 back for every £1 paid in. You've been told this already but like all Brexiters you ignore inconvenient truths. Oh, and stop playing the victim.</div>
    </blockquote>
    <br>
    I'm not playing the victim.</div>
    </blockquote>
    <br>
    Look Eve the facts you believe that led to your choice are wrong, you were misinformed which has led you to think that leaving the EU will be good for the country. Leaving the EU will be a disaster, it might be worth asking your brother that voted to remain to explain it to you, maybe you will be more willing to listen to him?</div>
    </blockquote>
    <br>
    Well I would still vote to leave the EU.</div>
    </blockquote>
    <br>
    That's because you posted your most telling comment earlier. All this guff about worrying about judges in Brussels and financial contributions are just a smoke screen for your real issue of deciding who we let in. That's what all this whole brexit comes down to. Ill informed racists like you.</div>
    </blockquote>
    <br>
    Do you actually have reason to call the poster racist? Or are you just being nasty?</div>
    </blockquote>
    <br>
    I realise you're a leaver so will only pick and choose the facts you want to read and acknowledge but for the avoidance of doubt my reason is clearly stated in the very post of mine you have quoted</div>
    </blockquote>
    <br>
    What is your reason for panelling the poster "racist"? Have they said anything racist? I haven't seen it so since you are. Making the accusation you can explain why.</div>
    </blockquote>
    <br>
    I've already explained. Maybe you can put a different spin on "deciding who we let in the country"??</div>
    </blockquote>
    <br>
    This is by no means the worst.<br><br><b>Please do something about it.<br></b></div>
</blockquote>`;
