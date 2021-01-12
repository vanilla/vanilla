/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { ImageEmbed } from "@library/embeddedContent/ImageEmbed";
import { EmbedContext } from "@library/embeddedContent/IEmbedContext";
import { userContentClasses } from "@library/content/userContentStyles";
import classNames from "classnames";

const story = storiesOf("Embeds", module);

// tslint:disable:jsx-use-translation-function

const date = "2019-06-05 20:59:01";

function EditorContent({ children }) {
    const classes = userContentClasses();
    return <div className={classNames("ql-editor", "richEditor-text", "userContent", classes.root)}>{children}</div>;
}

function EmbedWrapper({ children }) {
    return (
        <div className="js-embed embedResponsive isMounted embed-isSelected">
            <EmbedContext.Provider value={{ inEditor: true, isSelected: true }}>{children}</EmbedContext.Provider>
        </div>
    );
}

story.add("ImageEmbed", () => {
    return (
        <>
            <StoryHeading depth={1}>COMPONENT: ImageEmbed</StoryHeading>
            <EditorContent>
                <EmbedWrapper>
                    <ImageEmbed
                        type="image/png"
                        size={0}
                        name="hero image.png"
                        embedType="image"
                        dateInserted={date}
                        url="https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80"
                    />
                </EmbedWrapper>
                <EmbedWrapper>
                    <ImageEmbed
                        type="image/png"
                        size={0}
                        name="hero image.png"
                        embedType="image"
                        dateInserted={date}
                        displaySize="medium"
                        url="https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80"
                    />
                </EmbedWrapper>
                <EmbedWrapper>
                    <ImageEmbed
                        type="image/png"
                        size={0}
                        name="hero image.png"
                        embedType="image"
                        dateInserted={date}
                        float="left"
                        displaySize="small"
                        url="https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80"
                    />
                </EmbedWrapper>
                <p>
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin quis sodales ex. Fusce tortor eros,
                    facilisis in nunc a, dictum consequat dolor. Donec sed sem commodo arcu elementum fermentum et
                    lobortis massa. Sed consequat laoreet tincidunt. Etiam vel erat consectetur, hendrerit mauris
                    porttitor, sagittis leo. Maecenas fermentum, ipsum et ullamcorper consectetur, mi mi cursus tortor,
                    vel lobortis neque augue quis lectus.
                </p>
                <p>
                    In ligula eros, ultrices eu fringilla non, aliquam vel nisl. Integer id odio nec velit varius
                    tincidunt. Duis quis pharetra lectus, quis venenatis felis. In eget elit elit. Donec suscipit,
                    sapien sed mollis egestas, tellus neque dapibus leo, quis suscipit libero metus non purus. Fusce
                    lacinia enim eu ligula mollis, sit amet mollis elit congue. Proin eu felis efficitur, dapibus dui
                    ornare, dapibus eros. Ut neque massa, interdum at feugiat eget, dignissim eget augue. Praesent
                    vulputate nisi tempus justo cursus, sit amet bibendum tellus volutpat.
                </p>
                <EmbedWrapper>
                    <ImageEmbed
                        type="image/png"
                        size={0}
                        name="hero image.png"
                        embedType="image"
                        dateInserted={date}
                        float="right"
                        displaySize="small"
                        url="https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80"
                    />
                </EmbedWrapper>
                <p>
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin quis sodales ex. Fusce tortor eros,
                    facilisis in nunc a, dictum consequat dolor. Donec sed sem commodo arcu elementum fermentum et
                    lobortis massa. Sed consequat laoreet tincidunt. Etiam vel erat consectetur, hendrerit mauris
                    porttitor, sagittis leo. Maecenas fermentum, ipsum et ullamcorper consectetur, mi mi cursus tortor,
                    vel lobortis neque augue quis lectus.
                </p>
                <p>
                    In ligula eros, ultrices eu fringilla non, aliquam vel nisl. Integer id odio nec velit varius
                    tincidunt. Duis quis pharetra lectus, quis venenatis felis. In eget elit elit. Donec suscipit,
                    sapien sed mollis egestas, tellus neque dapibus leo, quis suscipit libero metus non purus. Fusce
                    lacinia enim eu ligula mollis, sit amet mollis elit congue. Proin eu felis efficitur, dapibus dui
                    ornare, dapibus eros. Ut neque massa, interdum at feugiat eget, dignissim eget augue. Praesent
                    vulputate nisi tempus justo cursus, sit amet bibendum tellus volutpat.
                </p>
                <EmbedWrapper>
                    <ImageEmbed
                        type="image/png"
                        size={0}
                        name="hero image.png"
                        embedType="image"
                        dateInserted={date}
                        float="left"
                        displaySize="medium"
                        url="https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80"
                    />
                </EmbedWrapper>
                <p>
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin quis sodales ex. Fusce tortor eros,
                    facilisis in nunc a, dictum consequat dolor. Donec sed sem commodo arcu elementum fermentum et
                    lobortis massa. Sed consequat laoreet tincidunt. Etiam vel erat consectetur, hendrerit mauris
                    porttitor, sagittis leo. Maecenas fermentum, ipsum et ullamcorper consectetur, mi mi cursus tortor,
                    vel lobortis neque augue quis lectus.
                </p>
                <p>
                    In ligula eros, ultrices eu fringilla non, aliquam vel nisl. Integer id odio nec velit varius
                    tincidunt. Duis quis pharetra lectus, quis venenatis felis. In eget elit elit. Donec suscipit,
                    sapien sed mollis egestas, tellus neque dapibus leo, quis suscipit libero metus non purus. Fusce
                    lacinia enim eu ligula mollis, sit amet mollis elit congue. Proin eu felis efficitur, dapibus dui
                    ornare, dapibus eros. Ut neque massa, interdum at feugiat eget, dignissim eget augue. Praesent
                    vulputate nisi tempus justo cursus, sit amet bibendum tellus volutpat.
                </p>
            </EditorContent>
        </>
    );
});
