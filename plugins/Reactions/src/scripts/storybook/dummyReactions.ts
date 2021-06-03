/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IReaction } from "@Reactions/types/Reaction";

export const dummyReactionsData: { [key: string]: IReaction } = {
    Promote: {
        tagID: 3,
        urlcode: "Promote",
        name: "Promote",
        class: "Positive",
        count: 2456,
        photoUrl: "https://badges.v-cdn.net/reactions/50/promote.png",
        url: "",
    },
    Disagree: {
        tagID: 6,
        urlcode: "Disagree",
        name: "Disagree",
        class: "Negative",
        count: 65,
        photoUrl: "https://badges.v-cdn.net/reactions/50/disagree.png",
        url: "",
    },
    Agree: {
        tagID: 7,
        urlcode: "Agree",
        name: "Agree",
        class: "Positive",
        count: 2,
        photoUrl: "https://badges.v-cdn.net/reactions/50/agree.png",
        url: "",
    },
    Like: {
        tagID: 9,
        urlcode: "Like",
        name: "Like",
        class: "Positive",
        count: 1023,
        photoUrl: "https://badges.v-cdn.net/reactions/50/like.png",
        url: "",
    },
    LOL: {
        tagID: 14,
        urlcode: "LOL",
        name: "LOL",
        class: "Positive",
        count: 13245,
        photoUrl: "https://badges.v-cdn.net/reactions/50/lol.png",
        url: "",
    },
    Spam: {
        tagID: 1,
        urlcode: "Spam",
        name: "Spam",
        class: "Flag",
        count: 1,
        photoUrl: "https://badges.v-cdn.net/reactions/50/spam.png",
        url: "",
    },
    Abuse: {
        tagID: 2,
        urlcode: "Abuse",
        name: "Abuse",
        class: "Flag",
        count: 29,
        photoUrl: "https://badges.v-cdn.net/reactions/50/abuse.png",
        url: "",
    },
};
