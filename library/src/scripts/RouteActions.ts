/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IRouteError } from "@library/RouteReducer";
import actionCreatorFactory from "typescript-fsa";

const createNavAction = actionCreatorFactory("@@navigation");
const createServerPageAction = actionCreatorFactory("@@serverPage");

export default class RouteActions {
    public static serverErrorAC = createServerPageAction<{ data: IRouteError }>("ERROR");
    public static errorAC = createNavAction<IRouteError>("ERROR");
    public static resetAC = createNavAction("RESET");
}
