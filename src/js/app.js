import Message from "./dep";
import loadPolyfills from './polyfills';

loadPolyfills().then(() => {
    console.log(Message);
})
