## NMC Splash

JS script to redirect first time visitors to a separate splash page. The cookie is set to expire after 30 days.

### Index template (/index.html)

This script should be included near the top of the `head` in the index.html template so users are redirected before most of the page elements are loaded. It should be added on each page where users should be redirected from (generally all but contribution pages).

jQuery is not needed, so it's best to avoid using it on the splash page in order to keep the page weight down.

The first argument is the cookie name. The second is the path to the splash page.

```html
{% js 'NMC-splash.js' min %}
<script>
    NMC.Splash.baseSetup("_splash-new","/welcome");
</script>
```

### Splash page template (e.g. /page/url/welcome.html)

A cookie is set as soon as the user arrives at the splash page.

Add the id `#splash-continue` to a link element to link users back to the original site entrance page.

The first argument is the cookie name.

```html
{% js 'NMC-splash.js' min %}
<script>
    NMC.Splash.splashSetup("_splash-new");
</script>
```
