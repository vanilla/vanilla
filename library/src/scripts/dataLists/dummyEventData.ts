import { EventAttendance } from "@library/events/eventOptions";
import { IEventExtended } from "@library/events/EventDetails";
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
        "Zoom meeting with Bob Ross. It's a super day, so why not make a beautiful sky? You can create the world you want to see and be a part of. You have that power. Talent is a pursued interest. That is to say, anything you practice you can do.\n" +
        "\n" +
        "If there's two big trees invariably sooner or later there's gonna be a little tree. You don't want to kill all your dark areas they are very important. We want to use a lot pressure while using no pressure at all. See. We take the corner of the brush and let it play back-and-forth. Let's put some highlights on these little trees. The sun wouldn't forget them.",
    going: [
        {
            userID: 100,
            name: "",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/392/n8FYRVKDYW0B7.jpeg",
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/284/nE6EM8EWJHFG0.jpg",
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/700/nH3YMJOEYZEM9.jpg",
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/003/n1H8CMV9TD4QA.png",
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/914/nFDVYLAK3OF99.jpg",
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/585/nJRB5AWOR08JH.jpg",
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/789/nMQU49KC9G3QO.png",
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/601/n0BPHALV761EE.jpg",
            dateLastActive: null,
        },
        {
            userID: 100,
            name: "",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/215/nPEGCS3DPD40I.jpg",
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
