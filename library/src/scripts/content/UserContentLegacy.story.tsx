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
        </blockquote>

       `;
        return <UserContent content={content} />;
    })
    .addDecorator(legacyCssDecorator);
