/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Enzyme from "enzyme";
import Adapter from "enzyme-adapter-react-16";
import "@babel/polyfill";

// Setup enzyme
Enzyme.configure({ adapter: new Adapter() });
