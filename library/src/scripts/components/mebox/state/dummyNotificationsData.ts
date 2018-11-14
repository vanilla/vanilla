/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { INotificationsDropDownProps } from "@library/components/mebox/pieces/NotificationsDropDown";

export const dummyNotificationsData: INotificationsDropDownProps = {
    userSlug: "admin",
    count: 1000,
    data: [
        {
            unread: false,
            userInfo: {
                userID: 1,
                name: "Tom",
                photoUrl: "https://pbs.twimg.com/profile_images/911337163488247809/xUFM7Ugx_400x400.jpg",
                dateLastActive: null,
            },
            message: '<0/> commented on the discussion: "How do I command a space ship?"',
            timestamp: "2018-10-22T16:56:37.423Z",
            to: "/kb/#todo",
        },
        {
            unread: false,
            userInfo: {
                userID: 1,
                name: "Tom",
                photoUrl: "https://pbs.twimg.com/profile_images/911337163488247809/xUFM7Ugx_400x400.jpg",
                dateLastActive: null,
            },
            message: '<0/> commented on the discussion: "How do I command a space ship?"',
            timestamp: "2018-10-22T16:56:37.423Z",
            to: "/kb/#todo",
        },
        {
            unread: true,
            warning: true,
            message: "<0/> been warned.",
            timestamp: "2018-10-22T16:56:37.423Z",
            to: "/kb/#todo",
        },
    ],
};
