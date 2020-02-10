/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IUserFragment } from "@vanilla/library/src/scripts/@types/api/users";

export const STORY_IMAGE =
    "https://user-images.githubusercontent.com/1770056/74069119-5dda5580-49cb-11ea-883b-61b7463c8cfc.png";

export const STORY_IPSUM_LONG =
    "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";

export const STORY_IPSUM_MEDIUM = STORY_IPSUM_LONG.slice(0, 160) + "…";

export const STORY_IPSUM_SHORT = STORY_IPSUM_LONG.slice(0, 50) + "…";

export const STORY_DATE = "2019-05-05T15:51:23+00:00";

export const STORY_USER: IUserFragment = {
    userID: 1,
    name: "Joe",
    dateLastActive: "2016-07-25 17:51:15",
    photoUrl: "https://user-images.githubusercontent.com/1770056/74098133-6f625100-4ae2-11ea-8a9d-908d70030647.png",
    label: "User Label",
};
