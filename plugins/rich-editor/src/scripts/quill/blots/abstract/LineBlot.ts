/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ClassFormatBlot from "@rich-editor/quill/blots/abstract/ClassFormatBlot";
import withWrapper from "@rich-editor/quill/blots/abstract/withWrapper";

/**
 * A Line blot is responsible for recreating it's wrapping Blots.
 */
class LineBlot extends ClassFormatBlot {}

export default withWrapper(LineBlot);
