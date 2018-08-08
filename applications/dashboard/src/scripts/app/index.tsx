/**
 * Wire together the different parts of the application.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { addRoutes, addComponent, onReady } from "@dashboard/application";
import React from "react";
import { Route } from "react-router-dom";
import SignInPage from "@dashboard/app/authenticate/SignInPage";
import PasswordPage from "@dashboard/app/authenticate/PasswordPage";
import RecoverPasswordPage from "@dashboard/app/authenticate/RecoverPasswordPage";
import Router from "@dashboard/components/Router";
import { stickyHeader } from "@dashboard/dom";
import { registerReducer } from "@dashboard/state/reducerRegistry";
import authenticateReducer from "@dashboard/state/authenticate/authenticateReducer";
import usersReducer from "@dashboard/state/users/usersReducer";
import { initCodePenEmbeds } from "@dashboard/app/user-content/embeds/codepen";
import { initGettyEmbeds } from "@dashboard/app/user-content/embeds/getty";
import { initGiphyEmbeds } from "@dashboard/app/user-content/embeds/giphy";
import { initImageEmbeds } from "@dashboard/app/user-content/embeds/image";
import { initImgurEmbeds } from "@dashboard/app/user-content/embeds/imgur";
import { initInstagramEmbeds } from "@dashboard/app/user-content/embeds/instagram";
import { initLinkEmbeds } from "@dashboard/app/user-content/embeds/link";
import { initSoundcloudEmbeds } from "@dashboard/app/user-content/embeds/soundcloud";
import { initTwitterEmbeds } from "@dashboard/app/user-content/embeds/twitter";
import { initVideoEmbeds } from "@dashboard/app/user-content/embeds/video";
import { initEmojiSupport } from "@dashboard/app/user-content/emoji";
import { initSpoilers } from "@dashboard/app/user-content/spoilers";
import { initQuoteEmbeds } from "@dashboard/app/user-content/embeds/quote";

// User content
initEmojiSupport();
initSpoilers();
initCodePenEmbeds();
initGettyEmbeds();
initGiphyEmbeds();
initImageEmbeds();
initImgurEmbeds();
initInstagramEmbeds();
initLinkEmbeds();
initSoundcloudEmbeds();
initTwitterEmbeds();
initVideoEmbeds();
initQuoteEmbeds();

// Redux
registerReducer("authenticate", authenticateReducer);
registerReducer("users", usersReducer);

// Routing
addComponent("App", Router);
addRoutes([
    <Route exact path="/authenticate/signin" component={SignInPage} />,
    <Route exact path="/authenticate/password" component={PasswordPage} />,
    <Route exact path="/authenticate/recoverpassword" component={RecoverPasswordPage} />,
]);

// Other site initializations
onReady(() => {
    stickyHeader();
});
