/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { QuoteEmbed } from "@library/embeddedContent/QuoteEmbed";
import { IUserFragment } from "@library/@types/api/users";
import { ICategory } from "@vanilla/addon-vanilla/@types/api/categories";

const story = storiesOf("Embeds", module);

// tslint:disable:jsx-use-translation-function

const ipsum = `
<p>Quasar rich in mystery Apollonius of Perga concept of the number one rich in mystery! Apollonius of Perga, rogue, hearts of the stars, brain is the seed of intelligence dispassionate extraterrestrial observer finite but unbounded. Tingling of the spine kindling the energy hidden in matter gathered by gravity science Apollonius of Perga Euclid cosmic fugue gathered by gravity take root and flourish dream of the mind's eye descended from astronomers ship of the imagination vastness is bearable only through love with pretty stories for which there's little good evidence Orion's sword. Trillion a billion trillion Apollonius of Perga, not a sunrise but a galaxy rise the sky calls to us! Descended from astronomers?</p><p>Some Text Here. <code class="code codeInline" spellcheck="false">Code Inline</code> Some More Text</p><p><strong>Bold</strong></p><p><em>italic</em></p><p><strong><em>bold italic</em></strong></p><p><strong><em><s>bold italic strike</s></em></strong></p><p><a href="http://test.com/" rel="nofollow"><strong><em><s>bold italic strike link</s></em></strong></a></p><p>Some text with a mention in it&nbsp;<a class="atMention" data-username="Alex Other Name" data-userid="23" href="http://dev.vanilla.localhost/profile/Alex%20Other%20Name">@Alex Other Name</a>&nbsp;Another mention&nbsp;<a class="atMention" data-username="System" data-userid="1" href="http://dev.vanilla.localhost/profile/System">@System</a>.</p><p>Some text with emojis🤗🤔🤣.</p>
`;

const dummyUser: IUserFragment = {
    name: "Adam Charron",
    photoUrl: "https://us.v-cdn.net/5018160/uploads/userpics/809/nHZP3CA8JMR2H.jpg",
    userID: 4,
    dateLastActive: "2019-02-10T23:54:14+00:00",
};

const dummyDate = "2019-02-10T23:54:14+00:00";

story.add("QuoteEmbed", () => {
    return (
        <>
            {/*<StoryHeading depth={1}>COMPONENT: QuoteEmbed</StoryHeading>*/}

            {/*<StoryHeading>Standard</StoryHeading>*/}
            <QuoteEmbed
                name="Rearrange order of span elements on discussion list viewRearrange order of span elements on discussion list viewRearrange order of span elements on discussion list viewRearrange order of span elements on discussion list view"
                body={ipsum}
                insertUser={dummyUser}
                dateInserted={dummyDate}
                embedType="quote"
                url="#"
                discussionLink={"#"}
                postLink={"#"}
                category={{
                    categoryID: 1,
                    name: "Cool Cats",
                    url: "#",
                }}
                displayOptions={{
                    showUserLabel: true,
                    showDiscussionLink: true,
                    showPostLink: true,
                    showCategoryLink: true,
                }}
            />

            {/*<QuoteEmbed body={ipsum} insertUser={dummyUser} dateInserted={dummyDate} embedType="quote" url="#" />*/}

            {/*<StoryHeading>Expanded by default</StoryHeading>*/}
            {/*<QuoteEmbed*/}
            {/*    body={ipsum}*/}
            {/*    insertUser={dummyUser}*/}
            {/*    dateInserted={dummyDate}*/}
            {/*    embedType="quote"*/}
            {/*    url="#"*/}
            {/*    expandByDefault={true}*/}
            {/*/>*/}

            {/*<StoryHeading>Expanded by default</StoryHeading>*/}
            {/*<QuoteEmbed*/}
            {/*    body={ipsum}*/}
            {/*    insertUser={dummyUser}*/}
            {/*    dateInserted={dummyDate}*/}
            {/*    embedType="quote"*/}
            {/*    url="#"*/}
            {/*    expandByDefault={true}*/}
            {/*/>*/}

            {/*<StoryHeading>With title</StoryHeading>*/}
            {/*<QuoteEmbed*/}
            {/*    name="Test Title here"*/}
            {/*    body={ipsum}*/}
            {/*    insertUser={dummyUser}*/}
            {/*    dateInserted={dummyDate}*/}
            {/*    embedType="quote"*/}
            {/*    url="#"*/}
            {/*/>*/}

            {/*<StoryHeading>Very Small (No expander)</StoryHeading>*/}
            {/*<QuoteEmbed*/}
            {/*    name="Test Title here"*/}
            {/*    body={"<p>Hello World</p><p>Line 2</p>"}*/}
            {/*    insertUser={dummyUser}*/}
            {/*    dateInserted={dummyDate}*/}
            {/*    embedType="quote"*/}
            {/*    url="#"*/}
            {/*    expandByDefault={true}*/}
            {/*/>*/}
        </>
    );
});
