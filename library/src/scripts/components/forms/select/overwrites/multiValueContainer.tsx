/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { components } from "react-select";

/**
 * Overwrite for the multiValue component in React Select
 * Note that this is NOT a true react component and gets called within the react select plugin
 * @param props
 */
export default function multiValueContainer(props) {
    return <components.MultiValueContainer {...props} className="suggestedTextInput-token" />;
}
