import ClassFormatBlot from "./ClassFormatBlot";
import withWrapper, { IWrappable } from "./withWrapper";
import WrapperBlot from "./WrapperBlot";

/**
 * A Line blot is responsible for recreating it's wrapping Blots.
 */
class LineBlot extends ClassFormatBlot {}

export default withWrapper(LineBlot);
