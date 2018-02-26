/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import WrapperBlot from "./WrapperBlot";
import ClassFormatBlot  from "./ClassFormatBlot";
import { wrappedBlot } from "../quill-utilities";

class SpoilerLineBlot extends ClassFormatBlot {
    static blotName = "spoiler-line";
    static className = "spoiler-line";
    static tagName = 'p';
    static parentName = "spoiler-content";
}

export default wrappedBlot(SpoilerLineBlot);

class ContentBlot extends WrapperBlot {
    static className = 'spoiler-content';
    static blotName = 'spoiler-content';
    static parentName = 'spoiler';
}

export const SpoilerContentBlot = wrappedBlot(ContentBlot);

export class SpoilerWrapperBlot extends WrapperBlot {
    static className = 'spoiler';
    static blotName = 'spoiler';
}
