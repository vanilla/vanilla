/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import UserContent from "@library/content/UserContent";
import { storiesOf } from "@storybook/react";
import { legacyCssDecorator } from "@dashboard/__tests__/legacyCssDecorator";

storiesOf("User Content", module)
    .add("Legacy Content", () => {
        const content = `  
       <h2>Legacy Code - BB Code</h2>
                       
        <div class="bbcode_left">
            <img src="https://us.v-cdn.net/5022541/uploads/166/54V2AXRD4C0R.jpg" class="embedImage-img"/>
        </div>
        
        <div class="bbcode_center">
            <img src="https://us.v-cdn.net/5022541/uploads/166/54V2AXRD4C0R.jpg" class="embedImage-img"/>
        </div>
        
        <div class="bbcode_right">
            <img src="https://us.v-cdn.net/5022541/uploads/166/54V2AXRD4C0R.jpg" class="embedImage-img"/>
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
       `;
        return <UserContent content={content} />;
    })
    .addDecorator(legacyCssDecorator);
