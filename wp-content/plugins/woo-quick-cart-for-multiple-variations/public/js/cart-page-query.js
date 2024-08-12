jQuery(document).ready(function ($) {
    'use strict';

    var cleanUri;
    var uri = window.location.toString();
    var newurl = removeParam('pid', uri).toString();
    var urlparts = newurl.split('?');

    if ('' === urlparts[1]) {
        cleanUri = newurl.substring(0, newurl.indexOf('?'));
        window.history.replaceState({}, document.title, cleanUri);
    } else {
        window.history.replaceState({}, document.title, newurl);
    }
});

function removeParam(key, sourceURL) {
    var i;
    var rtn = sourceURL.split('?')[0],
        param,
        paramsArr = [],
        queryString = (-1 !== sourceURL.indexOf('?')) ? sourceURL.split('?')[1] : '';
    if ('' !== queryString) {
        paramsArr = queryString.split('&');
        for (i = paramsArr.length - 1; 0 <= i; i -= 1) {
            param = paramsArr[i].split('=')[0];
            if (param === key) {
                paramsArr.splice(i, 1);
            }
        }
        rtn = rtn + '?' + paramsArr.join('&');
    }
    return rtn;
}