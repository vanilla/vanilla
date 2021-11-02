/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IReaction } from "@Reactions/types/Reaction";

const reactionBaseUrl = "https://badges.v-cdn.net/reactions/50/";
export const dummyReactionsData: { [key: string]: IReaction } = {
    Promote: {
        tagID: 3,
        urlcode: "Promote",
        name: "Promote",
        class: "Positive",
        count: 2456,
        photoUrl: reactionBaseUrl + "promote.svg",
        url: "",
    },
    Disagree: {
        tagID: 6,
        urlcode: "Disagree",
        name: "Disagree",
        class: "Negative",
        count: 65,
        photoUrl: reactionBaseUrl + "disagree.svg",
        url: "",
    },
    Agree: {
        tagID: 7,
        urlcode: "Agree",
        name: "Agree",
        class: "Positive",
        count: 2,
        photoUrl: reactionBaseUrl + "agree.svg",
        url: "",
    },
    Like: {
        tagID: 9,
        urlcode: "Like",
        name: "Like",
        class: "Positive",
        count: 1023,
        photoUrl: reactionBaseUrl + "like.svg",
        url: "",
    },
    LOL: {
        tagID: 14,
        urlcode: "LOL",
        name: "LOL",
        class: "Positive",
        count: 13245,
        photoUrl: reactionBaseUrl + "lol.svg",
        url: "",
    },
    Spam: {
        tagID: 1,
        urlcode: "Spam",
        name: "Spam",
        class: "Flag",
        count: 1,
        photoUrl: reactionBaseUrl + "spam.svg",
        url: "",
    },
    Abuse: {
        tagID: 2,
        urlcode: "Abuse",
        name: "Abuse",
        class: "Flag",
        count: 29,
        photoUrl: reactionBaseUrl + "abuse.svg",
        url: "",
    },
    LongReaction: {
        tagID: 10,
        urlcode: "Abuse",
        name: "Very Long Reaction Name Should Exceed Available Space",
        class: "Flag",
        count: 221520,
        photoUrl: reactionBaseUrl + "abuse.svg",
        url: "",
    },
};
