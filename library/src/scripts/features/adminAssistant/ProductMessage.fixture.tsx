/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import type { ProductMessagesApi } from "@library/features/adminAssistant/ProductMessages.api";
import { STORY_IPSUM_LONG, STORY_IPSUM_MEDIUM } from "@library/storybook/storyData";
import { blessStringAsSanitizedHtml } from "@vanilla/dom-utils";
import random from "lodash-es/random";

export class ProductMessageFixture {
    public static message(overrides?: Partial<ProductMessagesApi.Message>): ProductMessagesApi.Message {
        const announcement: ProductMessagesApi.Message = {
            productMessageType: "announcement",
            announcementType: "Inbox",
            foreignUrl: "#",
            dateInserted: "2024-01-01T00:00:00Z",
            productMessageID: `message${random(1, 1000000)}`,
            name: "Check out the awesome new feature!",
            body: blessStringAsSanitizedHtml(
                `<p>This is a great new feature that you should check out!</p>${STORY_IPSUM_LONG}`,
            ),
            foreignInsertUser: UserFixture.createMockUser(),
            isDismissed: false,
            countViewers: 0,
            ...overrides,
        };
        return announcement;
    }

    public static messageEdit(overrides?: Partial<ProductMessagesApi.EditBody>): ProductMessagesApi.EditBody {
        return {
            name: "Check out the awesome new feature!",
            announcementType: "Inbox",
            body: JSON.stringify([{ type: "p", children: [{ text: "OMG Check me out!" }] }]),
            format: "rich2",
            foreignInsertUserID: 1,
            ctaLabel: "Reach out!",
            ctaUrl: "mailto:me@example.com",
            ...overrides,
        };
    }

    public static mockMessages(): ProductMessagesApi.Message[] {
        return [
            ProductMessageFixture.message({
                name: "This one has short content",
                body: STORY_IPSUM_MEDIUM,
            }),
            ProductMessageFixture.message({
                name: "This one has a ctaUrl and label",
                ctaLabel: "Manage Search Settings",
                ctaUrl: "#",
            }),
            ProductMessageFixture.message({
                name: "This one is dismissed",
                isDismissed: true,
            }),
            ProductMessageFixture.message({
                name: "Changes to Our Status Page",
                body: blessStringAsSanitizedHtml(`
    <p>Heyo friends; happy <a href="https://www.merriam-webster.com/dictionary/ur-#dictionary-entry-3" rel="nofollow noopener ugc">Ur</a>-Friday!</p><p>In the support team's ongoing effort to improve communication with our amazing customers, we've made some changes to our <a href="https://status.vanillaforums.com/" rel="nofollow noopener ugc">Status Page</a> (which you should really go subscribe to, if you haven't already).</p><p>Rather than lumping all of our communities together into a single historic graph, we coordinated with our development, platform, and product team to bring you a more-detailed view that takes into account our infrastructure.</p><p>You can now find both Corporate and Enterprise plan server status charts - as well as our webhooks &amp; queue system charts - grouped by region. To see the individual charts for any given reason, simply click the plus sign next to the region where your servers are hosted (most of you are in Canada/Montreal):</p><span class="embedExternal embedImage display-large float-none" data-embedjson="{&quot;url&quot;:&quot;https:\\/\\/us.v-cdn.net\\/6030677\\/uploads\\/T9TK20O5W431\\/image.png&quot;,&quot;name&quot;:&quot;image.png&quot;,&quot;type&quot;:&quot;image\\/png&quot;,&quot;size&quot;:331499,&quot;width&quot;:2810,&quot;height&quot;:1750,&quot;displaySize&quot;:&quot;large&quot;,&quot;float&quot;:&quot;none&quot;,&quot;embedType&quot;:&quot;image&quot;,&quot;embedStyle&quot;:&quot;rich_embed_card&quot;}">\n    <span class="embedExternal-content">\n        <a class="embedImage-link" href="https://us.v-cdn.net/6030677/uploads/T9TK20O5W431/image.png" rel="nofollow noopener ugc" target="_blank">\n            <img class="embedImage-img" src="https://us.v-cdn.net/6030677/uploads/T9TK20O5W431/image.png" alt="image.png" height="1750" width="2810" loading="lazy" data-display-size="large" data-float="none" data-type="image/png" data-embed-type="image" srcset="https://us.v-cdn.net/cdn-cgi/image/fit=scale-down,width=10/https://us.v-cdn.net/6030677/uploads/T9TK20O5W431/image.png 10w, https://us.v-cdn.net/cdn-cgi/image/fit=scale-down,width=300/https://us.v-cdn.net/6030677/uploads/T9TK20O5W431/image.png 300w, https://us.v-cdn.net/cdn-cgi/image/fit=scale-down,width=800/https://us.v-cdn.net/6030677/uploads/T9TK20O5W431/image.png 800w, https://us.v-cdn.net/cdn-cgi/image/fit=scale-down,width=1200/https://us.v-cdn.net/6030677/uploads/T9TK20O5W431/image.png 1200w, https://us.v-cdn.net/cdn-cgi/image/fit=scale-down,width=1600/https://us.v-cdn.net/6030677/uploads/T9TK20O5W431/image.png 1600w, https://us.v-cdn.net/6030677/uploads/T9TK20O5W431/image.png"></img></a>\n    </span>\n</span>\n<p>This will allow us to be much more precise when reporting issues and maintenance moving forward. Please feel free to let us know in the comments if you've any questions!</p>`),
            }),
            ProductMessageFixture.message({
                productMessageType: "personalMessage",
                name: "This is message just for you're site.",
            }),
        ];
    }
}
