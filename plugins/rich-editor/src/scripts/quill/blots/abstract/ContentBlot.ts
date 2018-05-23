/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import WrapperBlot from "./WrapperBlot";
import withWrapper from "./withWrapper";
import LineBlot from "./LineBlot";
import { Blot } from "quill/core";

/**
 * A Content blot is both a WrappedBlot and a WrapperBlot.
 */
const ContentBlot = withWrapper(WrapperBlot as any);

ContentBlot.allowedChildren = [LineBlot];

export default ContentBlot;
