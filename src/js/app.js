import loadPolyfills from './polyfills';
import events from './events';

events.onReady(() => {
    return loadPolyfills().then(() => {
        console.log("Test");
    });
})
