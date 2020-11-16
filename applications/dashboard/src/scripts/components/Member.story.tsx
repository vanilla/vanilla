/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Member from "@dashboard/components/Member";
import { MemberTable } from "@dashboard/components/MemberTable";
import TwoColumnLayout from "@library/layout/TwoColumnLayout";

export default {
    title: "Search/Members",
    parameters: {
        chromatic: {
            viewports: [1400, 500],
        },
    },
};

const one = {
    userCardInfo: {
        email: "test@example.com",
        userID: 1,
        name: "Valérie Robitaille",
        photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
        dateLastActive: "May 2014",
        label: "Product Manager",
        countDiscussions: 1001,
        countComments: 120,
    },
};

const two = {
    userCardInfo: {
        email: "test@example.com",
        userID: 1,
        name: "Valérie Robitaille d'Ontario",
        photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
        dateLastActive: "May 2014",
        label: "Product Manager",
        countDiscussions: 1001,
        countComments: 120,
    },
};

const badUserName = {
    userCardInfo: {
        email: "test@example.com",
        userID: 1,
        name: "Valérie RRRRRRRRR RRRRRRRRRRR RRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRR",
        photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
        dateLastActive: "May 14, 2014",
        label: "Product Manager",
        countDiscussions: 213,
        countComments: 19,
    },
};

export const MemberList = () => (
    <TwoColumnLayout
        mainTop={
            <MemberTable>
                <Member {...one} />
                <Member {...one} />
                <Member {...two} />
                <Member {...one} />
                <Member {...one} />
                <Member {...two} />
                <Member {...two} />
                <Member {...one} />
                <Member {...badUserName} />
            </MemberTable>
        }
    />
);

export const MemberListShort = () => (
    <TwoColumnLayout
        mainTop={
            <MemberTable>
                <Member {...one} />
                <Member {...one} />
                <Member {...two} />
                <Member {...one} />
                <Member {...one} />
                <Member {...two} />
                <Member {...two} />
                <Member {...one} />
            </MemberTable>
        }
    />
);
