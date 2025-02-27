/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import Translate from "@library/content/Translate";
import SmartLink from "@library/routing/links/SmartLink";
import { IUnsubscribeToken } from "@library/unsubscribe/unsubscribePage.types";

export class UnsubscribeFixture {
    // Base result data to be overwritten per test scenario
    public static TOKEN_RESULT_TEMPLATE = {
        activityID: 1,
        activityTypes: [],
        activityData: [],
        user: {
            userID: 2,
            name: "Test User",
            email: "test@email.com",
            photoUrl:
                "https://user-images.githubusercontent.com/1770056/74098133-6f625100-4ae2-11ea-8a9d-908d70030647.png",
        },
    };

    public static FETCH_RESULT_TEMPLATE = {
        preferences: [],
        followedContent: undefined,
        hasMultiple: false,
        isAllProcessed: false,
        isEmailDigest: false,
        isUnfollowContent: false,
    };

    // Notification unsubscribe has already been processed
    public static MOCK_PROCESSED_TOKEN =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJBY3Rpdml0eUlEIjoxLCJBY3Rpdml0eVR5cGVzIjpbIk1lbnRpb24iXSwiQWN0aXZpdHlEYXRhIjpbXSwiVXNlcklEIjoyLCJOYW1lIjoiVGVzdCBVc2VyIiwiRW1haWwiOiJ0ZXN0QGVtYWlsLmNvbSIsIlBob3RvVXJsIjoiaHR0cHM6Ly91c2VyLWltYWdlcy5naXRodWJ1c2VyY29udGVudC5jb20vMTc3MDA1Ni83NDA5ODEzMy02ZjYyNTEwMC00YWUyLTExZWEtOGE5ZC05MDhkNzAwMzA2NDcucG5nIn0.rAUe47FZ9bEk71w8F399qBMjTREZmcWob9q5bcwoqao" as IUnsubscribeToken;

    public static MOCK_PROCESSED_API_RESULT = {
        preferences: [],
        followCategory: [],
    };

    public static MOCK_PROCESSED_DATA = {
        ...this.TOKEN_RESULT_TEMPLATE,
        ...this.FETCH_RESULT_TEMPLATE,
        activityTypes: ["Mention"],
        isAllProcessed: true,
    };

    // Notification that the user earned a new badge
    public static MOCK_BADGE_TOKEN =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJBY3Rpdml0eUlEIjoxLCJBY3Rpdml0eVR5cGVzIjpbIkJhZGdlIl0sIkFjdGl2aXR5RGF0YSI6W10sIlVzZXJJRCI6MiwiTmFtZSI6IlRlc3QgVXNlciIsIkVtYWlsIjoidGVzdEBlbWFpbC5jb20iLCJQaG90b1VybCI6Imh0dHBzOi8vdXNlci1pbWFnZXMuZ2l0aHVidXNlcmNvbnRlbnQuY29tLzE3NzAwNTYvNzQwOTgxMzMtNmY2MjUxMDAtNGFlMi0xMWVhLThhOWQtOTA4ZDcwMDMwNjQ3LnBuZyJ9.2Hv5Q-fPoJdXuqV5-kIAte1xRk238ieCnnvxRWUzhCM" as IUnsubscribeToken;

    public static MOCK_BADGE_API_RESULT = {
        preferences: [{ preference: "Email.Badge", enabled: "0" }],
        followCategory: [],
    };

    public static MOCK_BADGE_RESUBSCRIBE_RESULT = {
        preferences: [{ preference: "Email.Badge", enabled: "1" }],
        followCategory: [],
    };

    public static MOCK_BADGE_DATA = {
        ...this.TOKEN_RESULT_TEMPLATE,
        ...this.FETCH_RESULT_TEMPLATE,
        isAllProcessed: true,
        activityTypes: ["Badge"],
        preferences: [
            {
                preferenceRaw: "Email.Badge",
                preferenceName: "Badge",
                enabled: true,
                label: <p key="Email.Badge">New badges</p>,
                optionID: "Email||Badge",
            },
        ],
    };

    public static MOCK_BADGE_RESUBSCRIBE_DATA = {
        ...this.TOKEN_RESULT_TEMPLATE,
        ...this.FETCH_RESULT_TEMPLATE,
        activityTypes: ["Badge"],
        isAllProcessed: true,
        preferences: [
            {
                ...this.MOCK_BADGE_DATA.preferences[0],
                enabled: true,
            },
        ],
    };

    // Notification that someone commented on a post they are participating in and is also in a category they are following
    public static MOCK_MULTI_TOKEN =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJBY3Rpdml0eUlEIjoxLCJBY3Rpdml0eVR5cGVzIjpbIlBhcnRpY2lwYXRlQ29tbWVudCJdLCJBY3Rpdml0eURhdGEiOnsiY2F0ZWdvcnkiOiJUZXN0IENhdGVnb3J5IiwicmVhc29ucyI6WyJwYXJ0aWNpcGF0ZWQiXX0sIlVzZXJJRCI6MiwiTmFtZSI6IlRlc3QgVXNlciIsIkVtYWlsIjoidGVzdEBlbWFpbC5jb20iLCJQaG90b1VybCI6Imh0dHBzOi8vdXNlci1pbWFnZXMuZ2l0aHVidXNlcmNvbnRlbnQuY29tLzE3NzAwNTYvNzQwOTgxMzMtNmY2MjUxMDAtNGFlMi0xMWVhLThhOWQtOTA4ZDcwMDMwNjQ3LnBuZyJ9.zrOLcYUXWEG9EM2xJBTuR5i4jWknQlKzMge20k1fDE8" as IUnsubscribeToken;

    public static MOCK_MULTI_API_RESULT = {
        followCategory: {
            categoryID: 1,
            enabled: "1",
            name: "Test Category",
            preference: "Preferences.Email.NewComment.1",
        },
        preferences: [{ preference: "Email.ParticipateComment", enabled: "1" }],
    };

    public static MOCK_SAVE_API_RESULT = {
        ...this.MOCK_MULTI_API_RESULT,
        preferences: [{ preference: "Email.ParticipateComment", enabled: "0" }],
    };

    public static MOCK_MULTI_DATA = {
        ...this.TOKEN_RESULT_TEMPLATE,
        ...this.FETCH_RESULT_TEMPLATE,
        hasMultiple: true,
        activityTypes: ["ParticipateComment"],
        activityData: {
            category: "Test Category",
            reasons: ["participated"],
        },
        followedContent: {
            contentID: 1,
            contentName: "Test Category",
            contentType: "category",
            contentUrl: undefined,
            enabled: true,
            label: (
                <p key="Preferences.Email.NewComment.1">
                    <SmartLink to="/categories/test-category">Test Category</SmartLink> | New comments on posts
                </p>
            ),
            preferenceName: "NewComment",
            preferenceRaw: "Preferences.Email.NewComment.1",
            optionID: "Preferences||Email||NewComment||1",
        },
        preferences: [
            {
                enabled: true,
                label: <p key="Email.ParticipateComment">New comments on posts I&apos;ve participated in</p>,
                preferenceName: "ParticipateComment",
                preferenceRaw: "Email.ParticipateComment",
                optionID: "Email||ParticipateComment",
            },
        ],
    };

    public static MOCK_SAVE_DATA = {
        ...this.MOCK_MULTI_DATA,
        preferences: [
            {
                ...this.MOCK_MULTI_DATA.preferences[0],
                enabled: false,
            },
        ],
    };

    // Landing page to unfollow a category
    public static MOCK_UNFOLLOW_TOKEN =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJBY3Rpdml0eUlEIjoxLCJBY3Rpdml0eVR5cGVzIjpbIlVuZm9sbG93Q2F0ZWdvcnkiXSwiQWN0aXZpdHlEYXRhIjp7ImNhdGVnb3J5IjoiVGVzdCBDYXRlZ29yeSJ9LCJVc2VySUQiOjIsIk5hbWUiOiJUZXN0IFVzZXIiLCJFbWFpbCI6InRlc3RAZW1haWwuY29tIiwiUGhvdG9VcmwiOiJodHRwczovL3VzZXItaW1hZ2VzLmdpdGh1YnVzZXJjb250ZW50LmNvbS8xNzcwMDU2Lzc0MDk4MTMzLTZmNjI1MTAwLTRhZTItMTFlYS04YTlkLTkwOGQ3MDAzMDY0Ny5wbmcifQ.HkyTPxvo1kbKaJxWrDE4SEPWtPK6HIuxplD9i5oTmds" as IUnsubscribeToken;

    public static MOCK_UNFOLLOW_API_RESULT = {
        preferences: [],
        followCategory: {
            categoryID: 1,
            enabled: "0",
            name: "Test Category",
            preference: "Preferences.follow.1",
        },
    };

    public static MOCK_UNFOLLOW_DATA = {
        ...this.TOKEN_RESULT_TEMPLATE,
        ...this.FETCH_RESULT_TEMPLATE,
        activityTypes: ["UnfollowCategory"],
        activityData: {
            category: "Test Category",
        },
        isUnfollowContent: true,
        followedContent: {
            contentID: 1,
            contentName: "Test Category",
            contentType: "category",
            contentUrl: undefined,
            enabled: false,
            preferenceName: "follow",
            preferenceRaw: "Preferences.follow.1",
            label: (
                <p>
                    <Translate
                        source="You are no longer following <0/>"
                        c0={<SmartLink to="/categories/test-category">Test Category</SmartLink>}
                    />
                </p>
            ),
            optionID: "Preferences||follow||1",
        },
    };

    // Landing page to unsubscribe from email digest
    public static MOCK_DIGEST_TOKEN =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJBY3Rpdml0eUlEIjoxLCJBY3Rpdml0eVR5cGVzIjpbIkRpZ2VzdEVuYWJsZWQiXSwiQWN0aXZpdHlEYXRhIjpbXSwiVXNlcklEIjoyLCJOYW1lIjoiVGVzdCBVc2VyIiwiRW1haWwiOiJ0ZXN0QGVtYWlsLmNvbSIsIlBob3RvVXJsIjoiaHR0cHM6Ly91c2VyLWltYWdlcy5naXRodWJ1c2VyY29udGVudC5jb20vMTc3MDA1Ni83NDA5ODEzMy02ZjYyNTEwMC00YWUyLTExZWEtOGE5ZC05MDhkNzAwMzA2NDcucG5nIn0.oLjnqaTHTCs6Zf6LIMFoTQAB6KIDQzeKobzzAg54S7k" as IUnsubscribeToken;

    public static MOCK_DIGEST_API_RESULT = {
        preferences: [
            {
                preference: "Email.DigestEnabled",
                enabled: "0",
            },
        ],
        followCategory: [],
    };

    public static MOCK_DIGEST_DATA = {
        ...UnsubscribeFixture.TOKEN_RESULT_TEMPLATE,
        ...UnsubscribeFixture.FETCH_RESULT_TEMPLATE,
        activityTypes: ["DigestEnabled"],
        isEmailDigest: true,
        preferences: [
            {
                preferenceRaw: "Email.DigestEnabled",
                preferenceName: "DigestEnabled",
                enabled: false,
                label: <></>,
                optionID: "Email||DigestEnabled",
            },
        ],
    };

    public static MOCK_DIGEST_HIDE_CATEGORY_TOKEN =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJBY3Rpdml0eUlEIjowLCJBY3Rpdml0eVR5cGVzIjpbIkZvbGxvd2VkQ2F0ZWdvcnkiXSwiQWN0aXZpdHlEYXRhIjp7ImNhdGVnb3J5IjoiVGVzdCBDYXRlZ29yeSJ9LCJVc2VySUQiOjIsIk5hbWUiOiJUZXN0IFVzZXIiLCJFbWFpbCI6InRlc3RAZW1haWwuY29tIiwiUGhvdG9VcmwiOiJodHRwczovL3VzZXItaW1hZ2VzLmdpdGh1YnVzZXJjb250ZW50LmNvbS8xNzcwMDU2Lzc0MDk4MTMzLTZmNjI1MTAwLTRhZTItMTFlYS04YTlkLTkwOGQ3MDAzMDY0Ny5wbmcifQ.M3niNjuqgJdaJaqbltyMk6mMXNwyzJtY-HTfprkU0Nc" as IUnsubscribeToken;

    public static MOCK_DIGEST_HIDE_CATEGORY_API_RESULT = {
        preferences: [
            {
                preference: "Email.Digest",
                enabled: "1",
                userID: 2,
            },
        ],
        followCategory: {
            categoryID: 1,
            preference: "Preferences.Email.Digest.1",
            name: "Test Category",
            enabled: "1",
            userID: 2,
        },
    };

    public static MOCK_DIGEST_HIDE_CATEGORY_DATA = {
        ...this.TOKEN_RESULT_TEMPLATE,
        ...this.FETCH_RESULT_TEMPLATE,
        activityTypes: ["FollowedCategory"],
        activityData: {
            category: "Test Category",
        },
        activityID: 0,
        isEmailDigest: false,
        isUnfollowContent: false,
        hasMultiple: true,
        preferences: [
            {
                preferenceRaw: "Email.Digest",
                preferenceName: "Digest",
                enabled: true,
                label: <></>,
                optionID: "Email||Digest",
            },
        ],
        followedContent: {
            contentID: 1,
            contentName: "Test Category",
            contentType: "category",
            contentUrl: undefined,
            enabled: true,
            label: <SmartLink to="/categories/test-category">Test Category</SmartLink>,
            preferenceName: "Digest",
            preferenceRaw: "Preferences.Email.Digest.1",
            optionID: "Preferences||Email||Digest||1",
        },
    };

    // Some mock data for other content following, e.g. groups

    public static MOCK_DIGEST_HIDE_OTHER_CONTENT_TOKEN =
        "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJVc2VySUQiOjIsIk5hbWUiOiJUZXN0IFVzZXIiLCJQaG90b1VybCI6Imh0dHBzOi8vdXNlci1pbWFnZXMuZ2l0aHVidXNlcmNvbnRlbnQuY29tLzE3NzAwNTYvNzQwOTgxMzMtNmY2MjUxMDAtNGFlMi0xMWVhLThhOWQtOTA4ZDcwMDMwNjQ3LnBuZyIsIkVtYWlsIjoidGVzdEBlbWFpbC5jb20iLCJBY3Rpdml0eVR5cGVzIjpbIkVtYWlsRGlnZXN0Il0sIkFjdGl2aXR5VHlwZSI6IkVtYWlsRGlnZXN0IiwiQWN0aXZpdHlJRCI6MCwiQWN0aXZpdHlEYXRhIjp7Ikdyb3VwTmFtZSI6Ik15IGN1c3RvbSBjb250ZW50IGFzIGdyb3VwIiwiR3JvdXBJRCI6MSwiVHlwZSI6Ikdyb3VwRGlnZXN0In19.9FeySLTPnnR8cGRP41IZGbtDj_7mZduSYCnFG1rcrTY" as IUnsubscribeToken;

    public static MOCK_DIGEST_HIDE_OTHER_CONTENT_API_RESULT = {
        preferences: [],
        followContent: {
            contentID: 1,
            contentType: "group",
            contentUrl: "/some-custom-content-url",
            preference: "Preferences.Email.Digest.1",
            name: "Test Other Content",
            enabled: "1",
            userID: 2,
        },
    };

    public static MOCK_DIGEST_HIDE_OTHER_CONTENT_DATA = {
        ...this.TOKEN_RESULT_TEMPLATE,
        ...this.FETCH_RESULT_TEMPLATE,
        activityTypes: ["EmailDigest"],
        activityData: {
            GroupID: 1,
            GroupName: "My custom content as group",
            Type: "GroupDigest",
        },
        activityID: 0,
        isEmailDigest: false,
        isUnfollowContent: false,
        hasMultiple: false,
        preferences: [],
        followedContent: {
            contentID: this.MOCK_DIGEST_HIDE_OTHER_CONTENT_API_RESULT.followContent.contentID,
            contentName: this.MOCK_DIGEST_HIDE_OTHER_CONTENT_API_RESULT.followContent.name,
            contentType: "group",
            contentUrl: this.MOCK_DIGEST_HIDE_OTHER_CONTENT_API_RESULT.followContent.contentUrl,
            enabled: true,
            label: (
                <SmartLink to={this.MOCK_DIGEST_HIDE_OTHER_CONTENT_API_RESULT.followContent.contentUrl}>
                    {this.MOCK_DIGEST_HIDE_OTHER_CONTENT_API_RESULT.followContent.name}
                </SmartLink>
            ),
            preferenceName: "Digest",
            preferenceRaw: "Preferences.Email.Digest.1",
            optionID: "Preferences||Email||Digest||1",
        },
    };
}
