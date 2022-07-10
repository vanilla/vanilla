import { injectGlobal } from "@emotion/css";

export const onlineUserWrapCSS = () => {
    injectGlobal({
        [".PhotoGrid .OnlineUserWrap, .Panel .PhotoGrid .OnlineUserWrap"]: {
            ["&.pageBox"]: {
                width: "auto",
            },
        },
    });
};
