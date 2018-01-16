/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

// import axios from "axios";

import axios from "axios";

const instance = axios.create();
instance.defaults.timeout = 2500;

// TODO: Add response interceptors to fire certain events.

export default instance;
