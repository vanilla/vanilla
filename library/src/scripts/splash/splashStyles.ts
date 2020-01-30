/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { BackgroundColorProperty, FontWeightProperty, PaddingProperty, TextShadowProperty } from "csstype";
import { important, percent, px, quote, translateX, ColorHelper, url, rgba } from "csx";
import {
    centeredBackgroundProps,
    fonts,
    getBackgroundImage,
    IFont,
    paddings,
    unit,
    colorOut,
    background,
    absolutePosition,
    modifyColorBasedOnLightness,
    EMPTY_FONTS,
    EMPTY_SPACING,
} from "@library/styles/styleHelpers";
import { assetUrl } from "@library/utility/appUtils";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { widgetVariables } from "@library/styles/widgetStyleVars";
import generateButtonClass from "@library/forms/styleHelperButtonGenerator";
import merge from "lodash/merge";
import cloneDeep from "lodash/cloneDeep";

export const splashFallbackBG =
    "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB2aWV3Qm94PSIwIDAgMTYwMCAyNTAiPgogIDxkZWZzPgogICAgPGxpbmVhckdyYWRpZW50IGlkPSJhIiB4MT0iMzk5LjYiIHkxPSItMzk4LjQ1NSIgeDI9IjEyMzguMTg1IiB5Mj0iNDQwLjEzIiBncmFkaWVudFRyYW5zZm9ybT0ibWF0cml4KDEsIDAsIDAsIC0xLCAwLCAyNTIpIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+CiAgICAgIDxzdG9wIG9mZnNldD0iMCIgc3RvcC1jb2xvcj0iIzlmYTJhNCIvPgogICAgICA8c3RvcCBvZmZzZXQ9IjEiIHN0b3AtY29sb3I9IiNkY2RkZGUiLz4KICAgIDwvbGluZWFyR3JhZGllbnQ+CiAgICA8bGluZWFyR3JhZGllbnQgaWQ9ImIiIHgxPSItODQ1NS43NTMiIHkxPSItMTUwMS40OSIgeDI9Ii01MzcwLjUzMyIgeTI9IjE1ODMuNzMiIGdyYWRpZW50VHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTEwMjguNTI0IDI1Mikgcm90YXRlKDE4MCkgc2NhbGUoMC4yNjQgMSkiIHhsaW5rOmhyZWY9IiNhIi8+CiAgICA8bGluZWFyR3JhZGllbnQgaWQ9ImMiIHgxPSIzOTAuMjQ3IiB5MT0iLTM4OS4xMDIiIHgyPSIxMTk3LjE5NyIgeTI9IjQxNy44NDgiIHhsaW5rOmhyZWY9IiNhIi8+CiAgICA8bGluZWFyR3JhZGllbnQgaWQ9ImQiIHgxPSIzOTkuNiIgeTE9Ii0zOTguNDU1IiB4Mj0iMTI0Ni41NTYiIHkyPSI0NDguNTAxIiB4bGluazpocmVmPSIjYSIvPgogICAgPGxpbmVhckdyYWRpZW50IGlkPSJlIiB4MT0iLTEwNDgyLjEyNSIgeTE9Ii0xMzkyLjI4IiB4Mj0iLTczMjUuNjc0IiB5Mj0iMTc2NC4xNzIiIGdyYWRpZW50VHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTE1NTAuMTM5IDMxMS40MDEpIHJvdGF0ZSgxODApIHNjYWxlKDAuMjY0IDEpIiB4bGluazpocmVmPSIjYSIvPgogICAgPGxpbmVhckdyYWRpZW50IGlkPSJmIiB4MT0iMjU5MC40NDMiIHkxPSItMTA4Mi4yMjkiIHgyPSI1MDI5Ljg0MyIgeTI9IjEzNTcuMTcxIiBncmFkaWVudFRyYW5zZm9ybT0ibWF0cml4KDAuMzM5LCAwLCAwLCAtMSwgLTQ4OS4zNTgsIDMxMS40MDEpIiB4bGluazpocmVmPSIjYSIvPgogICAgPGNsaXBQYXRoIGlkPSJnIj4KICAgICAgPHJlY3QgeD0iLTEuMiIgeT0iMC40NTUiIHdpZHRoPSIxNjAwIiBoZWlnaHQ9IjI1MCIgc3R5bGU9ImZpbGw6IG5vbmUiLz4KICAgIDwvY2xpcFBhdGg+CiAgPC9kZWZzPgogIDx0aXRsZT5HZW5lcmljIEJhY2tncm91bmQ8L3RpdGxlPgogIDxnIHN0eWxlPSJpc29sYXRpb246IGlzb2xhdGUiPgogICAgPHJlY3QgeD0iLTAuNCIgeT0iMC40NTUiIHdpZHRoPSIxNjAwIiBoZWlnaHQ9IjI1MCIgc3R5bGU9ImZpbGw6ICNmZmYiLz4KICAgIDxwYXRoIGQ9Ik0tLjQsMjUwLjQ1NXMxNTcuMi0xMjUuMiwzMjEuOS0xMjUsMjE3LjYsODcuMyw0ODguMSw4Ny4zLDQwOC0xNDkuNiw1NjUuOS0xNDkuNiwyMjQuMSwxMTguNCwyMjQuMSwxMTguNHY2OC45WiIgc3R5bGU9ImZpbGwtcnVsZTogZXZlbm9kZDttaXgtYmxlbmQtbW9kZTogbXVsdGlwbHk7ZmlsbDogdXJsKCNhKSIvPgogICAgPHBhdGggZD0iTTE2MDEuMiwyMDUuNzU1cy0xNTcuMi0xMjUuMi0zMjEuOS0xMjUtMjE3LjYsODcuMy00ODguMSw4Ny4zLTQwOC0xNDkuNS01NjUuOS0xNDkuNVMxLjIsMTM2Ljg1NSwxLjIsMTM2Ljg1NWwtMS42LDExMy42aDE2MDBaIiBzdHlsZT0iZmlsbC1ydWxlOiBldmVub2RkO29wYWNpdHk6IDAuNDMwMDAwMDA3MTUyNTU3O21peC1ibGVuZC1tb2RlOiBtdWx0aXBseTtpc29sYXRpb246IGlzb2xhdGU7ZmlsbDogdXJsKCNiKSIvPgogICAgPHBhdGggZD0iTS0uMiwyMTIuNzU1czE2Mi40LTE2OS43LDQ5Ni0xNDkuNmMyODIuOCwxNywzNzMuNiwxMjkuNSw1NjYuMSwxNDAuNywxOTIuNCwxMS4yLDUzMS44LDI2LjgsNTMxLjgsMjYuOGw2LDE5LjhILS40WiIgc3R5bGU9ImZpbGwtcnVsZTogZXZlbm9kZDtvcGFjaXR5OiAwLjQwMDAwMDAwNTk2MDQ2NTttaXgtYmxlbmQtbW9kZTogbXVsdGlwbHk7aXNvbGF0aW9uOiBpc29sYXRlO2ZpbGw6IHVybCgjYykiLz4KICAgIDxwYXRoIGQ9Ik0tLjQsMjUwLjQ1NXMxNzYuOC05NC41LDUzNy4yLTk0LjUsMzYzLjgsNzQuNiw1MjUsNzQuNiwyMTgtMjAzLjEsMzU2LjQtMjAzLjEsMTgxLjQsMjIzLDE4MS40LDIyM0gtLjRaIiBzdHlsZT0iZmlsbC1ydWxlOiBldmVub2RkO29wYWNpdHk6IDAuNDAwMDAwMDA1OTYwNDY1O21peC1ibGVuZC1tb2RlOiBtdWx0aXBseTtpc29sYXRpb246IGlzb2xhdGU7ZmlsbDogdXJsKCNkKSIvPgogICAgPHBhdGggZD0iTTE2MDAuNCwxMTYuOTU1bC0uOC0xMTYuNWMtMTcuMzgyLDAtMzcyLjMzMi0zLjE5NC0zODguMTEyLDEuNzc3QzExNTMuMjA1LDIwLjU5LDEwMTYuNTEzLDExOCw3NzAuMzg4LDExNi41LDU3Mi44LDExNS4zLDQ1OC4xLDI3LjQ1NSwzODAuMTczLS41NTVMLS40LjQ1NWwuOCw3Ny4xLS44LDE3Mi45aDE2MDBaIiBzdHlsZT0iZmlsbC1ydWxlOiBldmVub2RkO29wYWNpdHk6IDAuNDMwMDAwMDA3MTUyNTU3O21peC1ibGVuZC1tb2RlOiBtdWx0aXBseTtpc29sYXRpb246IGlzb2xhdGU7ZmlsbDogdXJsKCNlKSIvPgogICAgPHBhdGggZD0iTS41LDExNi45NTVzMTU2LjgtNzEuNiwzMjEuMS03MS41LDE2OC42LDcwLjc1OCw0MzguNSw3MC43NThTMTIxNS41LDkuOTU1LDEzNzMsOS45NTVzMjIzLjYsNjcuNywyMjMuNiw2Ny43bC44LDE3Mi45SDEuM1oiIHN0eWxlPSJmaWxsLXJ1bGU6IGV2ZW5vZGQ7b3BhY2l0eTogMC40MzAwMDAwMDcxNTI1NTc7bWl4LWJsZW5kLW1vZGU6IG11bHRpcGx5O2lzb2xhdGlvbjogaXNvbGF0ZTtmaWxsOiB1cmwoI2YpIi8+CiAgICA8ZyBzdHlsZT0ibWl4LWJsZW5kLW1vZGU6IG11bHRpcGx5Ij4KICAgICAgPGcgc3R5bGU9ImNsaXAtcGF0aDogdXJsKCNnKSI+CiAgICAgICAgPGcgc3R5bGU9Im9wYWNpdHk6IDAuNjk5OTk5OTg4MDc5MDcxIj4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjA5LjQsMjMwLjE1NWMtNTY3LjMsMjk3LjUtNjc3LjEtMTc2LjktMTUzMSwzNDQuOSIgc3R5bGU9ImZpbGw6IG5vbmU7c3Ryb2tlOiAjOTE5Mzk2O3N0cm9rZS1saW5lY2FwOiByb3VuZDtzdHJva2UtbGluZWpvaW46IHJvdW5kO3N0cm9rZS13aWR0aDogM3B4O3N0cm9rZS1kYXNoYXJyYXk6IDEsMTEiLz4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjA5LjIsMjEzLjk1NWMtNTgyLjksMzE3LjYtNzAyLjMtMTc0LjEtMTUzNi45LDMzMi4xIiBzdHlsZT0iZmlsbDogbm9uZTtzdHJva2U6ICM5MTkzOTY7c3Ryb2tlLWxpbmVjYXA6IHJvdW5kO3N0cm9rZS1saW5lam9pbjogcm91bmQ7c3Ryb2tlLXdpZHRoOiAyLjkzNTQ5OTkwNjUzOTkycHg7c3Ryb2tlLWRhc2hhcnJheTogMSwxMSIvPgogICAgICAgICAgPHBhdGggZD0iTTE2MDguOSwxOTcuNzU1Yy01OTguNCwzMzcuNy03MjcuNC0xNzEuMi0xNTQyLjcsMzE5LjQiIHN0eWxlPSJmaWxsOiBub25lO3N0cm9rZTogIzkxOTM5NjtzdHJva2UtbGluZWNhcDogcm91bmQ7c3Ryb2tlLWxpbmVqb2luOiByb3VuZDtzdHJva2Utd2lkdGg6IDIuODcxMDAwMDUxNDk4NDFweDtzdHJva2UtZGFzaGFycmF5OiAxLDExIi8+CiAgICAgICAgICA8cGF0aCBkPSJNMTYwOC42LDE4MS41NTVjLTYxMy45LDM1Ny44LTc1Mi41LTE2OC4zLTE1NDguNSwzMDYuNyIgc3R5bGU9ImZpbGw6IG5vbmU7c3Ryb2tlOiAjOTE5Mzk2O3N0cm9rZS1saW5lY2FwOiByb3VuZDtzdHJva2UtbGluZWpvaW46IHJvdW5kO3N0cm9rZS13aWR0aDogMi44MDY0OTk5NTgwMzgzM3B4O3N0cm9rZS1kYXNoYXJyYXk6IDEsMTEiLz4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjA4LjQsMTY1LjM1NUM5NzksNTQzLjI1NSw4MzAuNy0uMTQ1LDU0LDQ1OS4zNTUiIHN0eWxlPSJmaWxsOiBub25lO3N0cm9rZTogIzkxOTM5NjtzdHJva2UtbGluZWNhcDogcm91bmQ7c3Ryb2tlLWxpbmVqb2luOiByb3VuZDtzdHJva2Utd2lkdGg6IDIuNzQxODk5OTY3MTkzNnB4O3N0cm9rZS1kYXNoYXJyYXk6IDEsMTEiLz4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjA4LjEsMTQ5LjE1NWMtNjQ0LjksMzk4LTgwMi44LTE2Mi42LTE1NjAuMiwyODEuMyIgc3R5bGU9ImZpbGw6IG5vbmU7c3Ryb2tlOiAjOTE5Mzk2O3N0cm9rZS1saW5lY2FwOiByb3VuZDtzdHJva2UtbGluZWpvaW46IHJvdW5kO3N0cm9rZS13aWR0aDogMi42Nzc0MDAxMTIxNTIxcHg7c3Ryb2tlLWRhc2hhcnJheTogMSwxMSIvPgogICAgICAgICAgPHBhdGggZD0iTTE2MDcuOSwxMzIuOTU1Yy02NjAuNSw0MTguMS04MjgtMTU5LjgtMTU2NiwyNjguNSIgc3R5bGU9ImZpbGw6IG5vbmU7c3Ryb2tlOiAjOTE5Mzk2O3N0cm9rZS1saW5lY2FwOiByb3VuZDtzdHJva2UtbGluZWpvaW46IHJvdW5kO3N0cm9rZS13aWR0aDogMi42MTI5MDAwMTg2OTIwMnB4O3N0cm9rZS1kYXNoYXJyYXk6IDEsMTEiLz4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjA3LjYsMTE2Ljc1NWMtNjc2LDQzOC4yLTg1My4xLTE1Ni45LTE1NzEuOCwyNTUuOCIgc3R5bGU9ImZpbGw6IG5vbmU7c3Ryb2tlOiAjOTE5Mzk2O3N0cm9rZS1saW5lY2FwOiByb3VuZDtzdHJva2UtbGluZWpvaW46IHJvdW5kO3N0cm9rZS13aWR0aDogMi41NDgzOTk5MjUyMzE5M3B4O3N0cm9rZS1kYXNoYXJyYXk6IDEsMTEiLz4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjA3LjMsMTAwLjU1NWMtNjkxLjUsNDU4LjMtODc4LjItMTU0LTE1NzcuNiwyNDMuMSIgc3R5bGU9ImZpbGw6IG5vbmU7c3Ryb2tlOiAjOTE5Mzk2O3N0cm9rZS1saW5lY2FwOiByb3VuZDtzdHJva2UtbGluZWpvaW46IHJvdW5kO3N0cm9rZS13aWR0aDogMi40ODM5MDAwNzAxOTA0M3B4O3N0cm9rZS1kYXNoYXJyYXk6IDEsMTEiLz4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjA3LjEsODQuMzU1Yy03MDcsNDc4LjQtOTAzLjQtMTUxLjItMTU4My41LDIzMC40IiBzdHlsZT0iZmlsbDogbm9uZTtzdHJva2U6ICM5MTkzOTY7c3Ryb2tlLWxpbmVjYXA6IHJvdW5kO3N0cm9rZS1saW5lam9pbjogcm91bmQ7c3Ryb2tlLXdpZHRoOiAyLjQxOTM5OTk3NjczMDM1cHg7c3Ryb2tlLWRhc2hhcnJheTogMSwxMSIvPgogICAgICAgICAgPHBhdGggZD0iTTE2MDYuOCw2OC4xNTVjLTcyMi41LDQ5OC41LTkyOC41LTE0OC4zLTE1ODkuMywyMTcuNiIgc3R5bGU9ImZpbGw6IG5vbmU7c3Ryb2tlOiAjOTE5Mzk2O3N0cm9rZS1saW5lY2FwOiByb3VuZDtzdHJva2UtbGluZWpvaW46IHJvdW5kO3N0cm9rZS13aWR0aDogMi4zNTQ3OTk5ODU4ODU2MnB4O3N0cm9rZS1kYXNoYXJyYXk6IDEsMTEiLz4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjA2LjYsNTEuOTU1Yy03MzguMSw1MTguNi05NTMuNy0xNDUuNS0xNTk1LjIsMjA0LjkiIHN0eWxlPSJmaWxsOiBub25lO3N0cm9rZTogIzkxOTM5NjtzdHJva2UtbGluZWNhcDogcm91bmQ7c3Ryb2tlLWxpbmVqb2luOiByb3VuZDtzdHJva2Utd2lkdGg6IDIuMjkwMjk5ODkyNDI1NTRweDtzdHJva2UtZGFzaGFycmF5OiAxLDExIi8+CiAgICAgICAgICA8cGF0aCBkPSJNMTYwNi4zLDM1Ljc1NWMtNzUzLjYsNTM4LjctOTc4LjgtMTQyLjYtMTYwMSwxOTIuMiIgc3R5bGU9ImZpbGw6IG5vbmU7c3Ryb2tlOiAjOTE5Mzk2O3N0cm9rZS1saW5lY2FwOiByb3VuZDtzdHJva2UtbGluZWpvaW46IHJvdW5kO3N0cm9rZS13aWR0aDogMi4yMjU4MDAwMzczODQwM3B4O3N0cm9rZS1kYXNoYXJyYXk6IDEsMTEiLz4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjA2LDE5LjU1NUM4MzYuOSw1NzguMzU1LDYwMi4yLTEyMC4xNDUtLjcsMTk5LjA1NSIgc3R5bGU9ImZpbGw6IG5vbmU7c3Ryb2tlOiAjOTE5Mzk2O3N0cm9rZS1saW5lY2FwOiByb3VuZDtzdHJva2UtbGluZWpvaW46IHJvdW5kO3N0cm9rZS13aWR0aDogMi4xNjEyOTk5NDM5MjM5NXB4O3N0cm9rZS1kYXNoYXJyYXk6IDEsMTEiLz4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjA1LjgsMy4zNTVjLTc4NC42LDU3OC45LTEwMjktMTM2LjktMTYxMi42LDE2Ni44IiBzdHlsZT0iZmlsbDogbm9uZTtzdHJva2U6ICM5MTkzOTY7c3Ryb2tlLWxpbmVjYXA6IHJvdW5kO3N0cm9rZS1saW5lam9pbjogcm91bmQ7c3Ryb2tlLXdpZHRoOiAyLjA5NjgwMDA4ODg4MjQ1cHg7c3Ryb2tlLWRhc2hhcnJheTogMSwxMSIvPgogICAgICAgICAgPHBhdGggZD0iTTE2MDUuNS0xMi44NDVjLTgwMC4xLDU5OC45LTEwNTQuMi0xMzQtMTYxOC40LDE1NCIgc3R5bGU9ImZpbGw6IG5vbmU7c3Ryb2tlOiAjOTE5Mzk2O3N0cm9rZS1saW5lY2FwOiByb3VuZDtzdHJva2UtbGluZWpvaW46IHJvdW5kO3N0cm9rZS13aWR0aDogMi4wMzIyOTk5OTU0MjIzNnB4O3N0cm9rZS1kYXNoYXJyYXk6IDEsMTEiLz4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjA1LjMtMjkuMDQ1Qzc4OS42LDU5MC4wNTUsNTI2LTE2MC4xNDUtMTksMTEyLjI1NSIgc3R5bGU9ImZpbGw6IG5vbmU7c3Ryb2tlOiAjOTE5Mzk2O3N0cm9rZS1saW5lY2FwOiByb3VuZDtzdHJva2UtbGluZWpvaW46IHJvdW5kO3N0cm9rZS13aWR0aDogMS45Njc3MDAwMDQ1Nzc2NHB4O3N0cm9rZS1kYXNoYXJyYXk6IDEsMTEiLz4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjA1LTQ1LjI0NWMtODMxLjIsNjM5LjItMTEwNC40LTEyOC4zLTE2MzAuMSwxMjguNiIgc3R5bGU9ImZpbGw6IG5vbmU7c3Ryb2tlOiAjOTE5Mzk2O3N0cm9rZS1saW5lY2FwOiByb3VuZDtzdHJva2UtbGluZWpvaW46IHJvdW5kO3N0cm9rZS13aWR0aDogMS45MDMyMDAwMzAzMjY4NHB4O3N0cm9rZS1kYXNoYXJyYXk6IDEsMTEiLz4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjA0LjctNjEuNDQ1Qzc1OCw1OTcuODU1LDQ3NS4yLTE4Ni44NDUtMzEuMiw1NC40NTUiIHN0eWxlPSJmaWxsOiBub25lO3N0cm9rZTogIzkxOTM5NjtzdHJva2UtbGluZWNhcDogcm91bmQ7c3Ryb2tlLWxpbmVqb2luOiByb3VuZDtzdHJva2Utd2lkdGg6IDEuODM4NzAwMDU2MDc2MDVweDtzdHJva2UtZGFzaGFycmF5OiAxLDExIi8+CiAgICAgICAgICA8cGF0aCBkPSJNMTYwNC41LTc3LjY0NWMtODYyLjMsNjc5LjQtMTE1NC43LTEyMi42LTE2NDEuOCwxMDMuMSIgc3R5bGU9ImZpbGw6IG5vbmU7c3Ryb2tlOiAjOTE5Mzk2O3N0cm9rZS1saW5lY2FwOiByb3VuZDtzdHJva2UtbGluZWpvaW46IHJvdW5kO3N0cm9rZS13aWR0aDogMS43NzQxOTk5NjI2MTU5N3B4O3N0cm9rZS1kYXNoYXJyYXk6IDEsMTEiLz4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjA0LjItOTMuODQ1Yy04NzcuNyw2OTkuNS0xMTc5LjgtMTE5LjctMTY0Ny41LDkwLjQiIHN0eWxlPSJmaWxsOiBub25lO3N0cm9rZTogIzkxOTM5NjtzdHJva2UtbGluZWNhcDogcm91bmQ7c3Ryb2tlLWxpbmVqb2luOiByb3VuZDtzdHJva2Utd2lkdGg6IDEuNzA5Njk5OTg4MzY1MTdweDtzdHJva2UtZGFzaGFycmF5OiAxLDExIi8+CiAgICAgICAgICA8cGF0aCBkPSJNMTYwNC0xMTAuMDQ1Yy04OTMuMyw3MTkuNi0xMjA1LTExNi44LTE2NTMuNCw3Ny43IiBzdHlsZT0iZmlsbDogbm9uZTtzdHJva2U6ICM5MTkzOTY7c3Ryb2tlLWxpbmVjYXA6IHJvdW5kO3N0cm9rZS1saW5lam9pbjogcm91bmQ7c3Ryb2tlLXdpZHRoOiAxLjY0NTIwMDAxNDExNDM4cHg7c3Ryb2tlLWRhc2hhcnJheTogMSwxMSIvPgogICAgICAgICAgPHBhdGggZD0iTTE2MDMuNy0xMjYuMjQ1Yy05MDguOCw3MzkuNy0xMjMwLjEtMTE0LTE2NTkuMiw2NSIgc3R5bGU9ImZpbGw6IG5vbmU7c3Ryb2tlOiAjOTE5Mzk2O3N0cm9rZS1saW5lY2FwOiByb3VuZDtzdHJva2UtbGluZWpvaW46IHJvdW5kO3N0cm9rZS13aWR0aDogMS41ODA2MDAwMjMyNjk2NXB4O3N0cm9rZS1kYXNoYXJyYXk6IDEsMTEiLz4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjAzLjQtMTQyLjM0NWMtOTI0LjMsNzU5LjctMTI1NS4yLTExMS4yLTE2NjUsNTIuMiIgc3R5bGU9ImZpbGw6IG5vbmU7c3Ryb2tlOiAjOTE5Mzk2O3N0cm9rZS1saW5lY2FwOiByb3VuZDtzdHJva2UtbGluZWpvaW46IHJvdW5kO3N0cm9rZS13aWR0aDogMS41MTYxMDAwNDkwMTg4NnB4O3N0cm9rZS1kYXNoYXJyYXk6IDEsMTEiLz4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjAzLjItMTU4LjU0NWMtOTM5LjksNzc5LjgtMTI4MC40LTEwOC4zLTE2NzAuOSwzOS40IiBzdHlsZT0iZmlsbDogbm9uZTtzdHJva2U6ICM5MTkzOTY7c3Ryb2tlLWxpbmVjYXA6IHJvdW5kO3N0cm9rZS1saW5lam9pbjogcm91bmQ7c3Ryb2tlLXdpZHRoOiAxLjQ1MTU5OTk1NTU1ODc4cHg7c3Ryb2tlLWRhc2hhcnJheTogMSwxMSIvPgogICAgICAgICAgPHBhdGggZD0iTTE2MDIuOS0xNzQuNzQ1Yy05NTUuMyw3OTkuOS0xMzA1LjUtMTA1LjUtMTY3Ni43LDI2LjciIHN0eWxlPSJmaWxsOiBub25lO3N0cm9rZTogIzkxOTM5NjtzdHJva2UtbGluZWNhcDogcm91bmQ7c3Ryb2tlLWxpbmVqb2luOiByb3VuZDtzdHJva2Utd2lkdGg6IDEuMzg3MDk5OTgxMzA3OThweDtzdHJva2UtZGFzaGFycmF5OiAxLDExIi8+CiAgICAgICAgICA8cGF0aCBkPSJNMTYwMi43LTE5MC45NDVjLTk3MC45LDgyMC0xMzMwLjctMTAyLjYtMTY4Mi42LDE0IiBzdHlsZT0iZmlsbDogbm9uZTtzdHJva2U6ICM5MTkzOTY7c3Ryb2tlLWxpbmVjYXA6IHJvdW5kO3N0cm9rZS1saW5lam9pbjogcm91bmQ7c3Ryb2tlLXdpZHRoOiAxLjMyMjYwMDAwNzA1NzE5cHg7c3Ryb2tlLWRhc2hhcnJheTogMSwxMSIvPgogICAgICAgICAgPHBhdGggZD0iTTE2MDIuNC0yMDcuMTQ1Yy05ODYuNCw4NDAuMS0xMzU1LjgtOTkuOC0xNjg4LjMsMS4zIiBzdHlsZT0iZmlsbDogbm9uZTtzdHJva2U6ICM5MTkzOTY7c3Ryb2tlLWxpbmVjYXA6IHJvdW5kO3N0cm9rZS1saW5lam9pbjogcm91bmQ7c3Ryb2tlLXdpZHRoOiAxLjI1ODEwMDAzMjgwNjRweDtzdHJva2UtZGFzaGFycmF5OiAxLDExIi8+CiAgICAgICAgICA8cGF0aCBkPSJNMTYwMi4xLTIyMy4zNDVjLTEwMDEuOSw4NjAuMi0xMzgwLjktOTYuOS0xNjk0LjEtMTEuNSIgc3R5bGU9ImZpbGw6IG5vbmU7c3Ryb2tlOiAjOTE5Mzk2O3N0cm9rZS1saW5lY2FwOiByb3VuZDtzdHJva2UtbGluZWpvaW46IHJvdW5kO3N0cm9rZS13aWR0aDogMS4xOTM1MDAwNDE5NjE2N3B4O3N0cm9rZS1kYXNoYXJyYXk6IDEsMTEiLz4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjAxLjktMjM5LjU0NWMtMTAxNy41LDg4MC4zLTE0MDYuMS05NC0xNzAwLTI0LjIiIHN0eWxlPSJmaWxsOiBub25lO3N0cm9rZTogIzkxOTM5NjtzdHJva2UtbGluZWNhcDogcm91bmQ7c3Ryb2tlLWxpbmVqb2luOiByb3VuZDtzdHJva2Utd2lkdGg6IDEuMTI4OTk5OTQ4NTAxNTlweDtzdHJva2UtZGFzaGFycmF5OiAxLDExIi8+CiAgICAgICAgICA8cGF0aCBkPSJNMTYwMS42LTI1NS43NDVjLTEwMzIuOSw5MDAuNC0xNDMxLjItOTEuMi0xNzA1LjgtMzYuOSIgc3R5bGU9ImZpbGw6IG5vbmU7c3Ryb2tlOiAjOTE5Mzk2O3N0cm9rZS1saW5lY2FwOiByb3VuZDtzdHJva2UtbGluZWpvaW46IHJvdW5kO3N0cm9rZS13aWR0aDogMS4wNjQ0OTk5NzQyNTA3OXB4O3N0cm9rZS1kYXNoYXJyYXk6IDEsMTEiLz4KICAgICAgICAgIDxwYXRoIGQ9Ik0xNjAxLjQtMjcxLjk0NWMtMTA0OC41LDkyMC41LTE0NTYuNC04OC4zLTE3MTEuNy00OS42IiBzdHlsZT0iZmlsbDogbm9uZTtzdHJva2U6ICM5MTkzOTY7c3Ryb2tlLWxpbmVjYXA6IHJvdW5kO3N0cm9rZS1saW5lam9pbjogcm91bmQ7c3Ryb2tlLWRhc2hhcnJheTogMSwxMSIvPgogICAgICAgIDwvZz4KICAgICAgPC9nPgogICAgPC9nPgogIDwvZz4KPC9zdmc+Cg==";

export const splashVariables = useThemeCache(styleOverwrite => {
    const makeThemeVars = variableFactory("splash");
    const globalVars = globalVariables();
    const widgetVars = widgetVariables();
    const formElVars = formElementsVariables();

    const options = makeThemeVars("options", {
        alignment: "center" as "left" | "center",
        imageType: "background" as "background" | "element",
    });

    const topPadding = 69;
    const spacing = makeThemeVars("spacing", {
        padding: {
            top: topPadding as PaddingProperty<TLength>,
            bottom: (topPadding * 0.8) as PaddingProperty<TLength>,
            right: unit(widgetVars.spacing.inner.horizontalPadding + globalVars.gutter.quarter) as PaddingProperty<
                TLength
            >,
            left: unit(widgetVars.spacing.inner.horizontalPadding + globalVars.gutter.quarter) as PaddingProperty<
                TLength
            >,
        },
    });

    const overwriteColors = styleOverwrite && styleOverwrite.colors ? styleOverwrite.colors : {};

    // Main colors
    const colors = makeThemeVars("colors", {
        primary: overwriteColors.primary ? overwriteColors.primary : globalVars.mainColors.primary,
        secondary: overwriteColors.secondary ? overwriteColors.secondary : globalVars.mainColors.secondary,
        contrast: overwriteColors.contrast ? overwriteColors.contrast : globalVars.elementaryColors.white,
        bg: overwriteColors.bg ? overwriteColors.bg : globalVars.mainColors.bg,
        fg: overwriteColors.fg ? overwriteColors.fg : globalVars.mainColors.fg,
        borderColor: overwriteColors.borderColor ? overwriteColors.borderColor : globalVars.mainColors.fg.fade(0.4),
    });

    const isContrastLight = colors.contrast instanceof ColorHelper && colors.contrast.lightness() >= 0.5;
    const backgrounds = makeThemeVars("backgrounds", {
        useOverlay: false,
        overlayColor: isContrastLight
            ? globalVars.elementaryColors.black.fade(0.3)
            : globalVars.elementaryColors.white.fade(0.3),
    });

    const outerBackground = makeThemeVars("outerBackground", {
        color: colors.primary,
        backgroundPosition: "50% 50%",
        backgroundSize: "cover",
        image: splashFallbackBG,
        fallbackImage: splashFallbackBG,
    });

    const innerBackground = makeThemeVars("innerBackground", {
        bg: undefined,
        padding: {
            top: spacing.padding,
            right: spacing.padding,
            bottom: spacing.padding,
            left: spacing.padding,
        },
    });

    const text = makeThemeVars("text", {
        shadowMix: 1, // We want to get the most extreme lightness contrast with text color (i.e. black or white)
        innerShadowOpacity: 0.25,
        outerShadowOpacity: 0.75,
    });

    const title = makeThemeVars("title", {
        maxWidth: 700,
        font: {
            ...EMPTY_FONTS,
            color: colors.contrast,
            size: globalVars.fonts.size.largeTitle,
            weight: globalVars.fonts.weights.semiBold as FontWeightProperty,
            align: options.alignment,
            shadow: `0 1px 1px ${colorOut(
                modifyColorBasedOnLightness(colors.contrast, text.shadowMix).fade(text.innerShadowOpacity),
            )}, 0 1px 25px ${colorOut(
                modifyColorBasedOnLightness(colors.contrast, text.shadowMix).fade(text.outerShadowOpacity),
            )}` as TextShadowProperty,
        },
        marginTop: 28,
        marginBottom: 40,
        text: "How can we help you?",
    });

    const description = makeThemeVars("description", {
        font: {
            ...EMPTY_FONTS,
            color: colors.contrast,
            size: globalVars.fonts.size.large,
            align: options.alignment,
        },
        maxWidth: 700,
        padding: {
            ...EMPTY_SPACING,
            top: 28,
            bottom: 40,
        },
    });

    const searchContainer = makeThemeVars("searchContainer", {
        width: 670,
    });

    const paragraph = makeThemeVars("paragraph", {
        margin: ".4em",
        text: {
            size: 24,
            weight: 300,
        },
    });

    const search = makeThemeVars("search", {
        margin: 30,
        fg: colors.contrast,
        bg: colors.contrast,
    });

    const searchDrawer = makeThemeVars("searchDrawer", {
        bg: colors.contrast,
    });

    enum SearchBarButtonType {
        TRANSPARENT = "transparent",
        SOLID = "solid",
    }

    const searchButtonOptions = makeThemeVars("searchButtonOptions", { type: SearchBarButtonType.SOLID });
    const isTransparentButton = searchButtonOptions.type === SearchBarButtonType.TRANSPARENT;

    const searchBar = makeThemeVars("searchBar", {
        sizing: {
            height: formElVars.giantInput.height,
            width: 705,
        },
        font: {
            color: colors.fg,
            size: formElVars.giantInput.fontSize,
        },
        border: {
            leftColor: isTransparentButton ? colors.contrast : colors.borderColor,
            width: globalVars.border.width,
            radius: {
                right: globalVars.border.radius,
                left: 0,
            },
        },
    });

    const shadow = makeThemeVars("shadow", {
        color: modifyColorBasedOnLightness(colors.contrast, text.shadowMix).fade(0.05),
        full: `0 1px 15px ${colorOut(modifyColorBasedOnLightness(colors.contrast, text.shadowMix).fade(0.3))}`,
        background: modifyColorBasedOnLightness(colors.contrast, text.shadowMix).fade(0.1) as BackgroundColorProperty,
    });

    // clean up and get rid of buttonTypeSplash / searchButton

    const bgColor = isTransparentButton ? "transparent" : colors.bg;
    const bgColorActive = isTransparentButton ? backgrounds.overlayColor.fade(0.15) : colors.secondary;
    const fgColor = isTransparentButton ? colors.contrast : colors.fg;
    const activeBorderColor = isTransparentButton ? colors.contrast : colors.bg;
    const searchButton: any = makeThemeVars("splashSearchButton", {
        name: "splashSearchButton",
        spinnerColor: colors.contrast,
        colors: {
            fg: fgColor,
            bg: bgColor,
        },
        borders: {
            ...(isTransparentButton
                ? {
                      color: colors.contrast,
                      width: 1,
                  }
                : { color: colors.bg, width: 0 }),
            left: {
                color: searchBar.border.leftColor,
                width: searchBar.border.width,
            },
            radius: {
                left: 0,
                ...searchBar.border.radius,
            },
        },
        fonts: {
            color: fgColor,
            size: globalVars.fonts.size.large,
            weight: globalVars.fonts.weights.semiBold,
        },
        hover: {
            colors: {
                fg: colors.contrast,
                bg: bgColorActive,
            },
            borders: {
                color: activeBorderColor,
            },
            fonts: {
                color: colors.contrast,
            },
        },
        active: {
            colors: {
                fg: colors.contrast,
                bg: bgColorActive,
            },
            borders: {
                color: activeBorderColor,
            },
            fonts: {
                color: colors.contrast,
            },
        },
        focus: {
            colors: {
                fg: colors.contrast,
                bg: bgColorActive,
            },
            borders: {
                color: activeBorderColor,
            },
            fonts: {
                color: colors.contrast,
            },
        },
        focusAccessible: {
            colors: {
                fg: colors.contrast,
                bg: bgColorActive,
            },
            borders: {
                color: activeBorderColor,
            },
            fonts: {
                color: colors.contrast,
            },
        },
    });

    return {
        options,
        outerBackground,
        backgrounds,
        spacing,
        searchContainer,
        innerBackground,
        text,
        title,
        description,
        paragraph,
        search,
        searchDrawer,
        searchBar,
        shadow,
        searchButton,
        colors,
    };
});

export const splashClasses = useThemeCache((styleOverwrite = {}) => {
    const vars = splashVariables(styleOverwrite);
    const style = styleFactory("splash");
    const formElementVars = formElementsVariables();
    const globalVars = globalVariables();

    const isCentered = vars.options.alignment === "center";
    const isImageBg = vars.options.imageType === "background";

    const root = style({
        position: "relative",
        backgroundColor: colorOut(vars.outerBackground.color),
    });

    const image = getBackgroundImage(vars.outerBackground.image, vars.outerBackground.fallbackImage);
    const outerBackground = (url?: string) => {
        const finalBackground = url ? { ...vars.outerBackground, image: url } : vars.outerBackground;
        return style("outerBackground", {
            ...centeredBackgroundProps(),
            display: "block",
            position: "absolute",
            top: px(0),
            left: px(0),
            width: percent(100),
            height: percent(100),
            ...background(finalBackground),
            opacity: finalBackground.image === splashFallbackBG ? 0.4 : undefined,
        });
    };

    const backgroundOverlay = style("backgroundOverlay", {
        display: "block",
        position: "absolute",
        top: px(0),
        left: px(0),
        width: percent(100),
        height: percent(100),
        background: colorOut(vars.backgrounds.overlayColor),
    });

    const innerContainer = style("innerContainer", {
        ...paddings(vars.spacing.padding),
        backgroundColor: vars.innerBackground.bg,
    });

    const text = style("text", {
        color: colorOut(vars.colors.contrast),
    });

    const searchButton = generateButtonClass(vars.searchButton);

    const valueContainer = style("valueContainer", {});

    const searchContainer = style("searchContainer", {
        position: "relative",
        maxWidth: percent(100),
        width: px(vars.searchContainer.width),
        margin: isCentered ? "auto" : undefined,
        $nest: {
            ".search-results": {
                maxWidth: percent(100),
                width: px(vars.searchContainer.width),
                margin: "auto",
                zIndex: 2,
            },
        },
    });

    const icon = style("icon", {});
    const input = style("input", {});

    const buttonLoader = style("buttonLoader", {});

    const title = style("title", {
        display: "block",
        ...fonts(vars.title.font as IFont),
        ...paddings({
            top: unit(vars.title.marginTop),
            bottom: unit(vars.title.marginBottom),
        }),
        flexGrow: 1,
    });

    const textWrapMixin: NestedCSSProperties = {
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "center",
        width: unit(vars.searchContainer.width),
        maxWidth: percent(100),
        margin: isCentered ? "auto" : undefined,
    };

    const titleAction = style("titleAction", {});
    const titleWrap = style("titleWrap", textWrapMixin);

    const titleFlexSpacer = style("titleFlexSpacer", {
        display: isCentered ? "block" : "none",
        position: "relative",
        height: unit(formElementVars.sizing.height),
        width: unit(formElementVars.sizing.height),
        flexBasis: unit(formElementVars.sizing.height),
        transform: translateX(px(formElementVars.sizing.height - globalVars.icon.sizes.default / 2 - 13)),
        $nest: {
            ".searchBar-actionButton:after": {
                content: quote(""),
                ...absolutePosition.middleOfParent(),
                width: px(20),
                height: px(20),
                backgroundColor: colorOut(vars.shadow.background),
                boxShadow: vars.shadow.full,
            },
            ".searchBar-actionButton": {
                color: important("inherit"),
                $nest: {
                    "&:not(.focus-visible)": {
                        outline: 0,
                    },
                },
            },
            ".icon-compose": {
                zIndex: 1,
            },
        },
    });

    const descriptionWrap = style("descriptionWrap", textWrapMixin);

    const description = style("description", {
        display: "block",
        ...fonts(vars.description.font as IFont),
        ...paddings(vars.description.padding),
        // flexGrow: 1,
    });

    const content = style("content", {
        $nest: {
            "&&.hasFocus .searchBar-valueContainer": {
                boxShadow: `0 0 0 ${unit(globalVars.border.width)} ${colorOut(vars.colors.primary)} inset`,
                zIndex: 1,
            },
        },
    });

    return {
        root,
        outerBackground,
        innerContainer,
        text,
        icon,
        searchContainer,
        searchButton,
        input,
        buttonLoader,
        title,
        titleAction,
        titleFlexSpacer,
        titleWrap,
        description,
        descriptionWrap,
        content,
        valueContainer,
        backgroundOverlay,
    };
});
