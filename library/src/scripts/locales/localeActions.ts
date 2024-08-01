/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ILocale } from "@vanilla/i18n";
import { actionCreatorFactory } from "typescript-fsa";
import { IApiError } from "@library/@types/api/core";

const createAction = actionCreatorFactory("@@locales");
export const getAllLocalesACs = createAction.async<{}, ILocale[], IApiError>("GET_ALL");
