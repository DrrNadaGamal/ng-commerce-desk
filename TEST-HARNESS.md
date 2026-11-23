# NG Commerce Desk source

Use the separate installable ZIP for WordPress. Plugin source, safety information,
English README, Arabic install guide and detailed Effect section are in ng-commerce/.
Screenshots are real WordPress/WooCommerce renders, all synthetic data.

## Optional isolated verification

Node 22+, npm and Chrome required. This harness downloads nothing from your accounts.

1. Run `npm install` here.
2. Download WooCommerce 11.3.1 from WordPress.org and extract the `woocommerce`
   directory into `plugins/woocommerce`. It is not bundled in this source ZIP.
3. Set CHROME_PATH if Chrome is not `/usr/bin/google-chrome`.
4. Run `npm run test:functional`, then `npm run test:layout`.

Each suite boots a fresh disposable WordPress Playground at 127.0.0.1:9410, seeds
synthetic objects and stops it afterward. Port 9410 must be free. Test fixture files
are briefly written in ng-commerce/ and removed in finally cleanup. Never publish
or deploy the verification harness or its runtime output. No saved credentials,
cookies or user data are included. The test suite is not a live-store test tool.

The comparison-fixture.php file is evidence source for the labelled plain-list
baseline only, not a production feature and not the standard WooCommerce admin UI.
No site or repository was changed by this package itself.
