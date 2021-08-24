/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Axios, { AxiosInstance } from "axios";
import React, { useContext } from "react";

export const ApiContext = React.createContext<AxiosInstance>(Axios);

export function useApiContext() {
    return useContext(ApiContext);
}
