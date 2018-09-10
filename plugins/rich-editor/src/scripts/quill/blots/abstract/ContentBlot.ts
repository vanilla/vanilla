/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import WrapperBlot from "@rich-editor/quill/blots/abstract/WrapperBlot";
import withWrapper from "@rich-editor/quill/blots/abstract/withWrapper";
import LineBlot from "@rich-editor/quill/blots/abstract/LineBlot";

/**
 * A Content blot is both a WrappedBlot and a WrapperBlot.
 */
const ContentBlot = withWrapper(WrapperBlot as any);

ContentBlot.allowedChildren = [LineBlot];

export default ContentBlot;
