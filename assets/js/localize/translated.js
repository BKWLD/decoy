/**
 * Get translation from global object
 */
define(function() {
    return function(key) {
        return window.LOCALIZATIONS[key];
    }
});
