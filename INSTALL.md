# Installing DeFlip 1.1.1

This bundle targets **SLiMS 9.8.0** and requires the eight host changes below. It is a manual deployment bundle, not a ZIP that can be uploaded as a standalone plugin. Confirm the target SLiMS version in `sysconfig.inc.php` and inspect its PHP/database environment before deployment. Syntax has been checked with PHP 8.4.12; other PHP versions and production installations are not certified by this bundle.

Review [THIRD_PARTY.md](THIRD_PARTY.md) before deployment. DearFlip Lite v1.7 has a non-commercial license; commercial use requires the appropriate vendor license. The plugin's GPL license does not remove that restriction.

## Bundle contents

- `plugins/deflip/`: plugin code, assets, original migration, and documentation.
- `integration/files/`: eight updated SLiMS host files, at their original relative paths.
- `integration/slims-9.8.0.patch`: unified diff for reviewing or merging those changes.
- `manifest.json`: SHA-256 for every payload file, and before/after SHA-256 for each host file.
- The ZIP's adjacent `.sha256` file: archive checksum.

| Host file | Purpose |
| --- | --- |
| `lib/contents/fstream.inc.php` | Pass the existing PDF password loader to DeFlip and validate attachment access. |
| `lib/contents/fstream-pdf.inc.php` | Stop after denied access; validate member restrictions before streaming. |
| `lib/detail.inc.php` | Open catalog PDF attachments in the modal and preserve access rules. |
| `admin/modules/bibliography/pop_attach.php` | Preserve password characters; validate form token, placement, and access choices. |
| `admin/modules/reporting/spreadsheet.php` | Preserve reader/contact values as text, keep access counts numeric, enforce reporting IP restrictions, and send the standard XLSX media type. |
| `js/updater.js` | Ignore stale admin AJAX responses. |
| `lib/Filesystems/Stream.php` | Preserve private/no-store PDF response headers without changing other callers. |
| `repository/.htaccess` | Block direct PDF requests on Apache so document access uses the authorized stream. |

The `baseline_sha256` values describe the local host files before this work. They do not represent every installation of SLiMS 9.8.0. A different version, local customization, or even different line endings can produce a mismatch.

## Prepare on staging

1. Create a staging copy with the production SLiMS version, PHP version, database schema, theme, and deployment path. Use the site's normal backup/deployment process. Preserve the production plugin directory, the eight host files, and a consistent database backup outside the public web directory.
2. Extract the release outside the SLiMS document root. Verify the ZIP checksum and the extracted payload against `manifest.json`. Inspect the patch before applying it.
3. Compare each target host file's SHA-256 with its `baseline_sha256` or `updated_sha256` in `host_integration`. If it already matches `updated_sha256`, that change is installed. If it matches `baseline_sha256`, the corresponding file in `integration/files/` can replace it. If neither matches, merge the relevant patch into the target file and review the result; do not overwrite that file wholesale.
4. Copy `plugins/deflip/` into the existing `plugins/deflip/` directory, keeping that directory name. Install the eight reviewed host changes together. Do not copy any local SLiMS configuration, database credentials, uploaded documents, or fixture files.
5. Use the existing **PDF.js** PDF viewer setting in SLiMS. Confirm the host has `js/pdfjs/build/ObjectPdf.js`, `js/ckeditor5/ckeditor.js`, HTMLPurifier, SLiMS CSRF/forms, and spreadsheet dependencies. These are host dependencies; the bundle does not replace them. Check that the database connection and PHP `mbstring`, `mysqli`, and `pdo_mysql` extensions are available. Follow the host's own requirements for spreadsheet export.
6. Open **System > Plugins** and enable **DearFlip**. When upgrading from an earlier version, the SLiMS plugin list marks the changed version as disabled until it is re-enabled. Re-enable it to save version 1.1.1. Keep existing `dflipConfig` values and guest/access records.
7. Open **System > DeFlip Settings**. Review guest registration, terms, and download-button settings. Clear the site's existing asset/OPcache caches using its normal deployment procedure, then reload the admin and catalog pages.

## Database migration

This release adds no new migration beyond the original `migration/1_CreateAndAlter.php`.

- A fresh activation runs migration 1 through the SLiMS migration runner. It adds nullable `files_read.guest_id`, changes `date_read` to the original timestamp definition, and creates `files_read_guest` using the original MyISAM engine. The database account needs the required `ALTER`/`CREATE` permissions during activation.
- An existing installation with plugin migration version 1 recorded skips that migration on upgrade. Verify the schema and migration metadata before activation; do not reset `db_version` to force a rerun.
- `SHOW COLUMNS FROM files_read LIKE 'guest_id';` and `SHOW TABLES LIKE 'files_read_guest';` are read-only checks for the required schema. Activation must succeed before enabling guest registration for readers.
- The original `down()` is empty. Disabling the plugin does not provide database rollback. Restore a consistent backup if migration rollback is necessary; do not drop guest/history data by hand.

## Acceptance checks before production

Perform these on staging with the reviewed production configuration:

1. Edit Terms, save, navigate away, reopen, and confirm the values persist. Quickly switch between DeFlip and System settings; only the selected page should remain.
2. Open public PDF attachments with stored Placement values Link, Popup, and Embed. Each PDF should open in the catalog modal. Complete the guest form personally, including agreement to the terms. Verify required fields, error messages, a mobile width, and the full-height book viewer.
3. Check the page arrows and toolbar on a normal PDF and a disposable encrypted PDF. Test correct, missing, and incorrect File Password values. Confirm hiding the download button changes the toolbar.
4. Use real member accounts with allowed and denied member types. Test public/private attachments in the catalog and both `p=fstream` and `p=fstream-pdf` endpoints. An unauthorized account must not receive the restricted PDF bytes.
5. Apply/reset report filters; open history; reduce rows per page to exercise a second page. Compare totals against the access records. Check print output, save the spreadsheet in a normal browser, and open the downloaded XLSX.
6. Check the browser Network/Console and PHP logs for new failures. When reviewing the viewer runtime, confirm `book.options.docParameters.isEvalSupported` is `false` before PDF loading. Repeat this with the site's deployed URL/subdirectory and HTTPS setup.

The download setting controls the toolbar, not DRM. File Password unlocks an already encrypted PDF; it does not encrypt the stored file.

## Storage and webserver access

The bundled Apache rule denies direct requests for PDF filenames beneath `repository/`. It requires Apache to honor that directory's `.htaccess` authorization rules. PHP streams remain accessible through SLiMS after attachment, member-type, and guest checks. Do not use direct repository PDF URLs in catalog integrations.

Nginx does not read `.htaccess`. Merge an equivalent rule into the site's existing server configuration, respecting the actual SLiMS subdirectory and location precedence. For a site installed at the root, the PDF rule is:

```nginx
location ~* ^/repository/.*\.pdf$ {
    return 403;
}
```

An existing `^~` location, alias, CDN, or external storage configuration may need its own rule. Keep remote PDF objects private and verify a disposable restricted PDF's direct URL returns no document bytes. These deployment controls cannot be established by copying PHP files alone. Verify HTTPS session cookie policies through the host's deployment configuration.

The included PDF.js 2.3.200 receives `isEvalSupported: false`, the Mozilla workaround for [CVE-2024-4367](https://github.com/mozilla/pdf.js/security/advisories/GHSA-wgrm-67xf-hhpq). DearFlip and its bundled libraries retain their original versions. Updating these libraries needs a separate compatibility review; this package is not a full dependency security certification.

## Production deployment and rollback

Deploy the same reviewed staging files during the site's deployment window after the acceptance checks pass. Take a fresh backup immediately before deployment. Avoid serving a partially copied plugin/core combination.

For a code rollback, disable DearFlip **without running migration down**, restore the previous plugin and all eight host files as one set, clear the deployment caches, and re-enable the restored version in System > Plugins. Confirm the original PDF viewer and admin navigation work. Keep the guest/history tables and settings unless a database restore is required.

For a fresh-install migration failure, inspect the schema before retrying because DDL/MyISAM changes are not guaranteed to roll back transactionally. If necessary, restore the pre-install database backup during the same maintenance window. Do not restore an old database after unrelated production writes without reconciling those writes.

## Rebuild the bundle

For maintainers, run `tools/build-release.py` with Python 3.10+ from this plugin. Supply an external output directory and the reviewed pre-change baseline directory containing `original-fstream.inc.php`, `original-fstream-pdf.inc.php`, `original-detail.inc.php`, `original-pop_attach.php`, `original-spreadsheet.php`, `original-updater.js`, `original-Stream.php`, and `original-repository.htaccess`. The builder uses only Python's standard library and does not deploy or access the database.

```powershell
python tools/build-release.py --baseline-dir C:/path/to/reviewed-baseline --output-dir C:/path/to/releases
```

The builder packages the plugin allowlist and the eight explicit host files, produces the review diff and manifest, and refuses to overwrite an existing ZIP. It excludes this build tool and any local configuration or database dump.
