/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import Enzyme from "enzyme";
import Adapter from "enzyme-adapter-react-16";
import { importAll } from "@testroot/utility";

// Setup enzyme
Enzyme.configure({ adapter: new Adapter() });

importAll((require as any).context("..", true, /.test.(ts|tsx)$/));
