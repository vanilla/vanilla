/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import IBaseState from "@dashboard/state/IState";
import { IMentionState } from "@rich-editor/state/mentionReducer";

export default interface IState extends IBaseState {
    editor: {
        mentions: IMentionState;
    };
}
