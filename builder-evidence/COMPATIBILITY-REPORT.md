# Builder compatibility addendum

NG Commerce Desk 1.0.0 and NG Learning Studio 1.0.0. Tested 23-24 November 2026 on disposable WordPress 7.1.5, PHP 8.3.36, SQLite/Playground, Chrome/Linux. WooCommerce 11.3.1 for Commerce. No production site or real data used. Plugin release code was not changed by these tests.

## Elementor free 4.4.1

Downloaded from the official WordPress plugin distribution: https://downloads.wordpress.org/plugin/elementor.latest-stable.zip

### Learning Studio

A real Elementor page was created with a native Shortcode widget containing `[ng_learning_demo]` and Elementor Canvas template. Underlying theme: Twenty Twenty-Five. The page data was seeded programmatically, not authored through the drag-and-drop editor.

- 11 checks passed: widget output, six EN/AR catalog page-overflow cases at 1440/390/320px, desktop synthetic enrolment/reader/completion, quiz score 100%, persisted completion after reload, no captured JavaScript errors.
- Another six checks passed on a fresh site at 320px: render, consent checked, physical enrol button click, physical reader completion click, completion persisted after reload, no JavaScript errors.
- The ordinary standalone functional suite had reached 66 passing checks before a later test-harness timeout in the initial combined run; this partial run is not presented as a full suite pass with Elementor.

### Commerce Desk

There is no Commerce shortcode or Elementor widget. Its actual admin/full-screen dashboard was tested while Elementor was activated, not embedded in an Elementor page.

- 26 checks passed: seed data, 24 EN/AR no-page-overflow cases across overview/orders/inventory/settings at 1440/390/320px, no JavaScript errors.
- A separate 17-check workflow passed: actual WooCommerce stock save via browser, stale/invalid stock rejection, inventory filtering, browser order status and internal note save, stale/terminal/oversized order update rejection, settings persistence/invalid setting rejection, no JavaScript errors.

## User-supplied modified Bricks fork, identifying as 2.4.2

The supplied archive SHA-256 is `66aa05c5f426bc3db0d3cdce9351a4790ee8cb1738af9efc5a9437206ecad068`. Its nested directory name and theme description identify an academy edition, and its code changes the vendor endpoint to a local address. This was not authenticated as an official Bricks trial or licensed distribution. Results apply only to this exact supplied modified package, not official Bricks compatibility. The package is not redistributed in this evidence archive. A preliminary source review and clean ZIP integrity check are not a malware/security certification.

The theme was mounted and activated only in disposable sites; outbound WordPress HTTP requests were blocked with a test-only MU plugin. No license bypass or theme source modifications were made by this test.

### Learning Studio

A native Shortcode element containing `[ng_learning_demo]` was saved into a Bricks page, with an authenticated admin context. Programmatic page setup only, not a drag-and-drop editor session.

- 11 checks passed, matching the Elementor widget/layout/desktop workflow above.
- Six additional fresh-site 320px physical enrolment/reader/completion/reload checks passed.

### Commerce Desk

- 26 dashboard seed/layout/JavaScript checks passed with this theme active.
- A separate 17-check stock/order/settings workflow passed, matching the Elementor test above.
- No claim of an embeddable Commerce widget or shortcode.

## Visual findings and limits

Actual screenshots were inspected for desktop and phone English/Arabic samples, Commerce dashboard and LMS quiz result. Embedded LMS remains usable but is not pixel-identical to standalone:

- On Elementor Canvas the fixed desktop sidebar interacts with the logged-in admin bar, causing a minor lower-profile overlap/crop.
- The supplied Bricks theme changes global form styles: desktop catalog filters stack and the learner label wraps; phone filters reflow differently. Content remained readable and no tested page exceeded viewport width.
- Standalone is the preferred presentation for matching the original design.

An initial long mixed-language/viewport test produced a consent warning after the automation reported checking it. This did not reproduce in fresh 320px physical-click tests on either builder, nor in the final desktop physical-click run. No plugin code workaround was applied. Treat it as an unreproduced sequence issue, not a confirmed fixed defect or a guarantee across all editor states.

Not verified: Elementor editor live-preview reinitialization after widget edits, drag-and-drop authoring, nested/narrow containers, Elementor Pro, official Bricks, other themes/versions/browsers, MySQL, a full accessibility audit, concurrent load or production use. All original synthetic-data, admin-only, shared-fixture and no-certificate limits still apply.

## Included evidence

Result logs and unaltered screenshots are under `evidence/`. The `tests/` directory contains the exact local verification scripts/blueprints for audit; it uses local mount paths and needs those paths adjusted to rerun. It is not an additional plugin release. No auth cookies, real learner data, premium theme, Elementor package, dependencies or runtime fixture values are included.

AI-assisted testing and implementation under Nada Gamal's direction.
