/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import ClassFormatBlot from "@rich-editor/quill/blots/abstract/ClassFormatBlot";
import withWrapper from "@rich-editor/quill/blots/abstract/withWrapper";

/**
 * A Line blot is responsible for recreating it's wrapping Blots.
 */
class LineBlot extends ClassFormatBlot {}

export default withWrapper(LineBlot);
