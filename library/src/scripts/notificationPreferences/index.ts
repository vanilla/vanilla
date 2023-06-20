/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import NotificationPreferences from "./NotificationPreferences";
import NotificationPreferencesApi from "./NotificationPreferences.api";

export * from "./NotificationPreferences.types";
export * from "./NotificationPreferences.context";
export * as utils from "./utils";
export * as classes from "./NotificationPreferences.classes";
export const api = NotificationPreferencesApi;
export default NotificationPreferences;
