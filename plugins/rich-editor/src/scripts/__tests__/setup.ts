/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import Enzyme from "enzyme";
import Adapter from "enzyme-adapter-react-16";
import { importAll } from "@testroot/utility";
import reducerRegistry from "@dashboard/state/reducerRegistry";
import editorReducer from "@rich-editor/state/editorReducer";
import registerQuill from "@rich-editor/quill/registerQuill";

// Setup enzyme
Enzyme.configure({ adapter: new Adapter() });
reducerRegistry.register("editor", editorReducer);
registerQuill();
importAll((require as any).context("..", true, /.test.(ts|tsx)$/));
