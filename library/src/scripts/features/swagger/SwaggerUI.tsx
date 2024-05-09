/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import SwaggerUI from "swagger-ui";
import "@library/features/swagger/swaggerStyles.scss";
import { injectGlobal } from "@emotion/css";
import { inputMixin } from "@library/forms/inputStyles";
import { disabledInput } from "@library/styles/styleHelpersFeedback";

injectGlobal({
    ".swagger-ui.swagger-ui .parameters-col_description > input": {
        ...inputMixin({
            font: { size: 14 },
        }),
        maxWidth: "100%",
        "&:disabled": disabledInput(),
    },
});

export { SwaggerUI };
