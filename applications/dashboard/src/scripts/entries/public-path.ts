// Set wepbacks public path.
const potentialPublicPath = window.gdn && window.gdn.meta && window.gdn.meta.WebRoot;
const finalPublicPath = potentialPublicPath != null ? potentialPublicPath : "/";
// @ts-ignore
__webpack_public_path__ = finalPublicPath;
