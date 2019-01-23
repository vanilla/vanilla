/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Enzyme from "enzyme";
import Adapter from "enzyme-adapter-react-16";
import { importAll } from "@library/__tests__/utility";
import reducerRegistry from "@library/state/reducerRegistry";
import editorReducer from "@rich-editor/state/editorReducer";
import registerQuill from "@rich-editor/quill/registerQuill";

// Setup enzyme
Enzyme.configure({ adapter: new Adapter() });
reducerRegistry.register("editor", editorReducer);
registerQuill();
importAll((require as any).context("..", true, /.test.(ts|tsx)$/));
