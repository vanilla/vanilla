/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EventAttendance } from "@groups/events/events/eventOptions";
import { IEventExtended } from "@groups/events/events/EventDetails";
import { IUserFragment } from "@library/@types/api/users";

export const dummyEventDetailsData = {
    name: "Watercolor for beginners",
    excerpt: "Zoom meeting with Bob Ross explaing the basic fundamentals of Watercolor",
    dateStart: {
        timestamp: "2020-04-22T14:31:19Z",
    },
    dateEnd: {
        timestamp: "2020-05-22T14:31:19Z",
    },
    location: "Your home",
    url: "http://google.ca",
    attendance: EventAttendance.MAYBE,
    organizer: "Elisa",
    about:
        "<p>Zoom meeting with Bob Ross. It's a super day, so why not make a beautiful sky? You can create the world you want to see and be a part of. You have that power. Talent is a pursued interest. That is to say, anything you practice you can do.</p><p>If there's two big trees invariably sooner or later there's gonna be a little tree. You don't want to kill all your dark areas they are very important. We want to use a lot pressure while using no pressure at all. See. We take the corner of the brush and let it play back-and-forth. Let's put some highlights on these little trees. The sun wouldn't forget them.</p>",
    going: [
        {
            userID: 100,
            name: "Val",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "Tim",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/700/nH3YMJOEYZEM9.jpg",
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "Mel",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/003/n1H8CMV9TD4QA.png",
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "Mysterious User",
            photoUrl: null,
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "Val",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "Tim",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/700/nH3YMJOEYZEM9.jpg",
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "Mel",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/003/n1H8CMV9TD4QA.png",
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "Alex",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/914/nFDVYLAK3OF99.jpg",
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "Val",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "Tim",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/700/nH3YMJOEYZEM9.jpg",
            dateLastActive: null,
        },
    ] as IUserFragment[],
    maybe: [
        {
            userID: 100,
            name: "",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/446/n2RXLCE65F21T.jpg",
            dateLastActive: null,
        },
    ] as IUserFragment[],
    notGoing: [] as IUserFragment[],
} as IEventExtended;
