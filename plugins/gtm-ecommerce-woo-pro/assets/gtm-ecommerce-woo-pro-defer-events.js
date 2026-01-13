window.dataLayer = window.dataLayer || [];
var deferEvents = true;
var deferredDataLayer = [];
var deferEventName = typeof event_deferral_setings !== "undefined"
    && event_deferral_setings.event_name || "gtm.load";
var deferTimeout = typeof event_deferral_setings !== "undefined"
    && event_deferral_setings.timeout || 1000;

setTimeout(function() {
    if (deferEvents === true) {
        deferEvents = false;
        deferredDataLayer.map(ev => dataLayer.push(ev));
        deferredDataLayer = [];
    }
}, deferTimeout);

window.dataLayer.push = new Proxy(window.dataLayer.push, {
    apply: (target, thisArg, argumentsList) => {
        var event = argumentsList[0]
        if (event.event === deferEventName && deferEvents === true) {
            deferEvents = false;
            Reflect.apply(target, thisArg, argumentsList);
            deferredDataLayer.map(ev => dataLayer.push(ev));
            deferredDataLayer = [];
            return true;
        } else if (undefined !== event.event && deferEvents === true && undefined !== event.ecommerce) {
            return deferredDataLayer.push(event);
        } else {
            return Reflect.apply(target, thisArg, argumentsList)
        }
    }
});
