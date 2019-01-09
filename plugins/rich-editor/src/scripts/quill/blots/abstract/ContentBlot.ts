/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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
