/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

declare namespace DiscussionsApi {
    import { IDiscussion } from "@dashboard/@types/api/discussion";
    export interface IndexParams {}

    export interface GetParams {
        expand?: string[];
    }

    export interface PatchParams extends Exclude<Partial<IDiscussion>, "discussionID"> {
        insertUserID?: string | number;
    }

    export interface DismissParams {
        dismissed?: boolean;
    }
}
