/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import ClassFormatBlot from "./ClassFormatBlot";
import withWrapper from "./withWrapper";

/**
 * A Line blot is responsible for recreating it's wrapping Blots.
 */
class LineBlot extends ClassFormatBlot {}

export default withWrapper(LineBlot);
