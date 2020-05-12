/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { IEvent } from "@library/events/Event";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { EventAttendance } from "@library/events/eventOptions";
import { EventList as EventListComponent } from "@library/events/EventList";

export default {
    title: "Event Lists",
    parameters: {
        chromatic: {
            viewports: [1450, layoutVariables().panelLayoutBreakPoints.xs],
        },
    },
};

const dummyEventData = [
    {
        name: "Watercolor for beginners",
        excerpt: "Zoom meeting with Bob Ross explaing the basic fundamentals of Watercolor",
        dateStart: {
            timestamp: "2020-04-22T14:31:19Z",
        },
        location: "Your home",
        url: "http://google.ca",
        attendance: EventAttendance.MAYBE,
    },
    {
        name: "Painting with acrilic",
        excerpt:
            "Making all those little fluffies that live in the clouds. Everything's not great in life, but we can still find beauty in it. No worries. No cares. Just float and wait for the wind to blow you around. Of course he's a happy little stone, cause we don't have any other kind. We don't have anything but happy trees here. Once you learn the technique, ohhh! Turn you loose on the world; you become a tiger. Didn't you know you had that much power? You can move mountains. You can do anything. Fluff that up. Let's do that again. We'll take a little bit of Van Dyke Brown. Nothing wrong with washing your brush. Just think about these things in your mind - then bring them into your world. A thin paint will stick to a thick paint. If you overwork it you become a cloud killer. There's nothing worse than a cloud killer. In this world, everything can be happy. Maybe there's a happy little waterfall happening over here. We'll throw some old gray clouds in here just sneaking around and having fun. The secret to doing anything is believing that you can do it. Anything that you believe you can do strong enough, you can do. Anything. As long as you believe. Be careful. You can always add more - but you can't take it away. This is truly an almighty mountain. We have no limits to our world. We're only limited by our imagination. We'll paint one happy little tree right here. Anytime you learn something your time and energy are not wasted. Every time you practice, you learn more Everyone needs a friend. Friends are the most valuable things in the world. It is a lot of fun. Let's make some happy little clouds in our world. I like to beat the brush. Just let these leaves jump off the brush This painting comes right out of your heart.",
        dateStart: {
            timestamp: "2020-04-22T14:31:19Z",
        },
        location: "Zoom meeting with Bob Ross explaing the basic fundamentals of Watercolor",
        url: "http://google.ca",
        attendance: EventAttendance.NOT_GOING,
    },
    {
        name:
            "From all of us here, I want to wish you happy painting and God bless, my friends. Just make little strokes like that. Let your imagination just wonder around when you're doing these things. We want to use a lot pressure while using no pressure at all. It is a lot of fun.",
        excerpt: "No worries. No cares. Just float and wait for the wind to blow you around.",
        dateStart: {
            timestamp: "2020-04-22T14:31:19Z",
        },
        location: "Winnipeg, MB",
        url: "http://google.ca",
        attendance: EventAttendance.GOING,
    },
    {
        name: "Just make little strokes like that.",
        dateStart: {
            timestamp: "2020-04-22T14:31:19Z",
        },
        url: "http://google.ca",
    },
    {
        name: "Watercolor for beginners",
        excerpt: "Zoom meeting with Bob Ross explaing the basic fundamentals of Watercolor",
        dateStart: {
            timestamp: "2020-04-22T14:31:19Z",
        },
        location: "Your home",
        url: "http://google.ca",
        attendance: EventAttendance.MAYBE,
    },
    {
        name: "Painting with acrilic",
        excerpt:
            "Making all those little fluffies that live in the clouds. Everything's not great in life, but we can still find beauty in it. No worries. No cares. Just float and wait for the wind to blow you around. Of course he's a happy little stone, cause we don't have any other kind. We don't have anything but happy trees here. Once you learn the technique, ohhh! Turn you loose on the world; you become a tiger. Didn't you know you had that much power? You can move mountains. You can do anything. Fluff that up. Let's do that again. We'll take a little bit of Van Dyke Brown. Nothing wrong with washing your brush. Just think about these things in your mind - then bring them into your world. A thin paint will stick to a thick paint. If you overwork it you become a cloud killer. There's nothing worse than a cloud killer. In this world, everything can be happy. Maybe there's a happy little waterfall happening over here. We'll throw some old gray clouds in here just sneaking around and having fun. The secret to doing anything is believing that you can do it. Anything that you believe you can do strong enough, you can do. Anything. As long as you believe. Be careful. You can always add more - but you can't take it away. This is truly an almighty mountain. We have no limits to our world. We're only limited by our imagination. We'll paint one happy little tree right here. Anytime you learn something your time and energy are not wasted. Every time you practice, you learn more Everyone needs a friend. Friends are the most valuable things in the world. It is a lot of fun. Let's make some happy little clouds in our world. I like to beat the brush. Just let these leaves jump off the brush This painting comes right out of your heart.",
        dateStart: {
            timestamp: "2020-04-22T14:31:19Z",
        },
        location: "Zoom meeting with Bob Ross explaing the basic fundamentals of Watercolor",
        url: "http://google.ca",
        attendance: EventAttendance.NOT_GOING,
    },
    {
        name: "Just make little strokes like that. Let your imagination just wonder around.",
        excerpt:
            "Making all those little fluffies that live in the clouds. Everything's not great in life, but we can still find beauty in it. No worries. No cares. Just float and wait for the wind to blow you around. Of course he's a happy little stone, cause we don't have any other kind. We don't have anything but happy trees here. Once you learn the technique, ohhh! Turn you loose on the world; you become a tiger. Didn't you know you had that much power? You can move mountains. You can do anything. Fluff that up. Let's do that again. We'll take a little bit of Van Dyke Brown. Nothing wrong with washing your brush. Just think about these things in your mind - then bring them into your world. A thin paint will stick to a thick paint. If you overwork it you become a cloud killer. There's nothing worse than a cloud killer. In this world, everything can be happy. Maybe there's a happy little waterfall happening over here. We'll throw some old gray clouds in here just sneaking around and having fun. The secret to doing anything is believing that you can do it. Anything that you believe you can do strong enough, you can do. Anything. As long as you believe. Be careful. You can always add more - but you can't take it away. This is truly an almighty mountain. We have no limits to our world. We're only limited by our imagination. We'll paint one happy little tree right here. Anytime you learn something your time and energy are not wasted. Every time you practice, you learn more Everyone needs a friend. Friends are the most valuable things in the world. It is a lot of fun. Let's make some happy little clouds in our world. I like to beat the brush. Just let these leaves jump off the brush This painting comes right out of your heart.",
        dateStart: {
            timestamp: "2020-04-22T14:31:19Z",
        },
        location: "Zoom meeting with Bob Ross explaing the basic fundamentals of Watercolor",
        url: "http://google.ca",
        attendance: EventAttendance.NOT_GOING,
    },
    {
        name: "Just make little strokes like that. Let your imagination just wonder around.",
        excerpt:
            "Making all those little fluffies that live in the clouds. Everything's not great in life, but we can still find beauty in it. No worries. No cares. Just float and wait for the wind to blow you around. Of course he's a happy little stone, cause we don't have any other kind. We don't have anything but happy trees here. Once you learn the technique, ohhh! Turn you loose on the world; you become a tiger. Didn't you know you had that much power? You can move mountains. You can do anything. Fluff that up. Let's do that again. We'll take a little bit of Van Dyke Brown. Nothing wrong with washing your brush. Just think about these things in your mind - then bring them into your world. A thin paint will stick to a thick paint. If you overwork it you become a cloud killer. There's nothing worse than a cloud killer. In this world, everything can be happy. Maybe there's a happy little waterfall happening over here. We'll throw some old gray clouds in here just sneaking around and having fun. The secret to doing anything is believing that you can do it. Anything that you believe you can do strong enough, you can do. Anything. As long as you believe. Be careful. You can always add more - but you can't take it away. This is truly an almighty mountain. We have no limits to our world. We're only limited by our imagination. We'll paint one happy little tree right here. Anytime you learn something your time and energy are not wasted. Every time you practice, you learn more Everyone needs a friend. Friends are the most valuable things in the world. It is a lot of fun. Let's make some happy little clouds in our world. I like to beat the brush. Just let these leaves jump off the brush This painting comes right out of your heart.",
        dateStart: {
            timestamp: "2020-04-22T14:31:19Z",
        },
        location: "Zoom meeting with Bob Ross explaing the basic fundamentals of Watercolor",
        url: "http://google.ca",
        attendance: EventAttendance.GOING,
    },
    {
        name:
            "From all of us here, I want to wish you happy painting and God bless, my friends. Just make little strokes like that. Let your imagination just wonder around when you're doing these things. We want to use a lot pressure while using no pressure at all. It is a lot of fun.",
        excerpt: "No worries. No cares. Just float and wait for the wind to blow you around.",
        dateStart: {
            timestamp: "2020-04-22T14:31:19Z",
        },
        location: "Winnipeg, MB",
        url: "http://google.ca",
        attendance: EventAttendance.GOING,
    },
    {
        name: "Just make little strokes like that.",
        dateStart: {
            timestamp: "2020-04-22T14:31:19Z",
        },
        url: "http://google.ca",
    },
] as IEvent[];

export function EventList(props: { title?: string; headingLevel?: 2 | 3 | 4; data?: IEvent[]; compact?: boolean }) {
    const { title = "Event List", headingLevel = 2, data = dummyEventData, compact = false } = props;
    return (
        <>
            <StoryHeading depth={1}>{title}</StoryHeading>
            <EventListComponent data={data} headingLevel={headingLevel} compact={compact} />
        </>
    );
}

export const EmptyList = storyWithConfig({}, () => <EventList title={"Empty Event List"} headingLevel={3} data={[]} />);
export const OneItemInList = storyWithConfig({}, () => (
    <EventList title={"One Event"} data={dummyEventData.slice(0, 1)} />
));

// Panel
export const PanelEventList = storyWithConfig({}, () => (
    <EventList title={"Panel - Event List"} compact={true} headingLevel={3} data={dummyEventData} />
));
export const EmptyPanelList = storyWithConfig({}, () => (
    <EventList title={"Panel - Empty Event List"} compact={true} headingLevel={3} data={[]} />
));
export const OneItemInPanelList = storyWithConfig({}, () => (
    <EventList title={"Panel - One Event"} compact={true} data={dummyEventData.slice(0, 1)} />
));
