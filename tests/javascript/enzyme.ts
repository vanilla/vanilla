/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import Enzyme from "enzyme";
import Adapter from "enzyme-adapter-react-16";

// Setup enzyme
Enzyme.configure({ adapter: new Adapter() });

export { default } from "enzyme";
export * from "enzyme";
