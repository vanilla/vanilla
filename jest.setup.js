/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

const enzyme = require("enzyme");
const Adapter = require("enzyme-adapter-react-16");
const registerRequireContextHook = require("babel-plugin-require-context-hook/register");
require("@testing-library/jest-dom/extend-expect");

enzyme.configure({ adapter: new Adapter() });
registerRequireContextHook();
