/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { IAuthenticateState } from "@dashboard/state/authenticate/IAuthenticateState";

export default interface IState {
    authenticate: IAuthenticateState;
}
