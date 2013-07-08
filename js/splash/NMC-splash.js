/**
 * JS script with cookie integration for redirecting visitors to a splash page. jQuery free.
 *
 * @author Tyler Pearson
 * @version 1.0
 */


var NMC = NMC || {};

NMC.util.Splash = (function () {

    "use strict";

    var daysBeforeCookieExpires = 30,
        createCookie = function(name, value, expires, path, domain) {
            var cookie = name + "=" + escape(value) + ";";
            if (expires) {
                if (expires instanceof Date) {
                    if (isNaN(expires.getTime())) {
                        expires = new Date();
                    }
                } else {
                    expires = new Date(new Date().getTime() + parseInt(expires, 10) * 1000 * 60 * 60 * 24);
                }
                cookie += "expires=" + expires.toGMTString() + ";";
            }
            if (path) {
                cookie += "path=" + path + ";";
            }
            if (domain) {
                cookie += "domain=" + domain + ";";
            }
            document.cookie = cookie;
        },
        getCookie = function(name) {
            var regexp = new RegExp("(?:^" + name + "|;\\s*" + name + ")=(.*?)(?:;|$)", "g"),
                result = regexp.exec(document.cookie);
            return (result === null) ? null : result[1];
        },
        readCookie = function(name) {
            var nameEQ = name + "=",
                ca = document.cookie.split(';'),
                i,
                c;
            for (i = 0; i < ca.length; i += 1) {
                c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1, c.length);
                }
                if (c.indexOf(nameEQ) === 0) {
                    return c.substring(nameEQ.length, c.length);
                }
            }
            return null;
        };

    return {
        baseSetup: function(cookieName, splashURL) {
            if (!(getCookie(cookieName))) {
                createCookie("splash-entrance", window.location.pathname, daysBeforeCookieExpires);
                window.location = splashURL;
            }
        },
        splashSetup: function(cookieName, expiresInDays) {
            if (expiresInDays) {
                daysBeforeCookieExpires = expiresInDays;
            }
            var link = document.getElementById('splash-continue');
            createCookie(cookieName, true, daysBeforeCookieExpires);
            if (!(readCookie("splash-entrance") === null)) {
                link.href = readCookie("splash-entrance");
            } else {
                link.href = "/";
            }
        }
    };

}());