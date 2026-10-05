# DeFlip for SLiMS

## DeFlip 1.1.1

This release targets the SLiMS 9.8.0 host integration. See [INSTALL.md](INSTALL.md) for installation, upgrade, migration, compatibility checks, and rollback. The release bundle includes the plugin and eight host integration files plus a reviewable patch and SHA-256 manifest. Copying only the plugin directory does not install every behavior in this release.

**Release status: staging acceptance required.** The release checks cover syntax, archive integrity, and ordinary local report/viewer UI checks. Member authorization, the complete guest flow, direct storage access, and spreadsheet saving still need acceptance checks on the destination environment before production deployment. This is not a completed runtime security audit.

The source repository stores the reviewed host changes in `integration/`. When installing source instead of the release bundle, copy the plugin runtime directories and files into `plugins/deflip`, and review/install the eight host changes as described in the installation guide. `tools/` and `integration/` do not need to be placed inside the runtime plugin directory.

Plugin/host code uses GPL v3. Bundled viewer libraries have their own terms, including DearFlip Lite's non-commercial restriction. See [THIRD_PARTY.md](THIRD_PARTY.md) before deployment.

DeFlip uses the existing SLiMS settings, authentication, report grid, CKEditor, CSRF support, and HTMLPurifier. No additional package or database migration is required for an installation with the original DeFlip migration already applied.

- **System > DeFlip Settings** manages guest registration, the reader download button, and terms and conditions. Rich text has a textarea fallback if CKEditor cannot start. Values are validated and terms are sanitized before storage and display.
- **Reporting > DeFlip Access Report** counts document openings. Repeated openings count separately; this is not a count of completed downloads or reading sessions. Date, bibliography, pagination, history, and spreadsheet filters use the same query criteria. A file linked to several collections contributes one row per access log; its displayed collection is the first linked bibliography ID.
- **View all reader details** lists every access record across collections. Both this view and **View history** show Reader, Institution, Phone Number, IP address, and Date alongside the document. Guest details come from the guest form; member details come from the member profile. Missing values appear as `-`. Print and spreadsheet output include the same columns; the DeFlip history spreadsheet stores values as text to preserve phone prefixes and prevent reader text being interpreted as formulas.
- **Print Current Page** produces an A4 landscape report with library identity, period, active filters, generation time, record count, and pagination context. Additional category/report headings are omitted. Semantic headers repeat across printed pages. It prints the currently displayed report page; export includes all records matching the filters.
- The guest popup contains only the reader form, terms, and continuation button. The reader displays only the book and its toolbar and fills the popup viewport.
- Hiding the download button changes the reader interface. PDF content is still delivered to the browser for reading.
- **Bibliography > File Attachment** retains title, description, and a valid HTTP/HTTPS resource URL in the catalog. PDF attachments always open in the collection modal. Placement (Link/Popup/Embed) continues to control link and video attachments.
- **File Password** supplies the password for an already encrypted PDF through the existing SLiMS session loader. It does not encrypt an unprotected document. A missing or incorrect password produces an access message in the reader. Public/Private and Member Type restrictions are checked before loading the viewer and again before streaming the PDF; malformed member limits deny access.
- The viewer passes `isEvalSupported: false` to PDF.js, applying Mozilla's documented workaround for [CVE-2024-4367](https://github.com/mozilla/pdf.js/security/advisories/GHSA-wgrm-67xf-hhpq). The bundled DearFlip/PDF.js versions remain unchanged; this mitigation does not claim a complete dependency security audit or compatibility with a newer PDF.js major version.

### Structure

`src/Settings.php`, `src/GuestAccess.php`, and `src/AccessReport.php` hold settings validation, guest registration, and report queries. `src/ReportGrid.php` extends the existing SLiMS grid with bounded page numbers, fixed report ordering, and semantic table headers. `pages/` handles SLiMS entry points; `views/` renders the guest form, filters, and print metadata. `assets/` contains scoped styles and scripts; `viewer/` retains the bundled DearFlip libraries.

Host integration changes are scoped to `lib/contents/fstream.inc.php` (passes the existing password loader to the plugin), `lib/contents/fstream-pdf.inc.php` (stops execution after a denied-access redirect), `lib/detail.inc.php` (opens PDFs in the catalog modal), and `admin/modules/bibliography/pop_attach.php` (preserves PDF password characters and explains placement). The attachment access checks reject malformed member limits. `js/updater.js` ignores outdated responses when several requests replace the same admin container. Plugin report navigation uses `notAJAX` for links with an explicit AJAX handler to avoid duplicate requests.

Attachment submissions validate the existing SLiMS form token and access/placement choices. `admin/modules/reporting/spreadsheet.php` sends the standard XLSX media type so spreadsheet downloads are recognized consistently by clients.

Version 1.1.1 enforces guest registration before PDF streaming, preserves private/no-store headers in `lib/Filesystems/Stream.php`, and blocks direct PDF requests in Apache through `repository/.htaccess`. Nginx and remote storage require equivalent deployment rules; see [INSTALL.md](INSTALL.md). Both DeFlip spreadsheet modes write untrusted text as explicit strings; access counts remain numeric. Reports accept 1–200 rows per page, recover stale pages, ignore unconfigured sort selectors, and preserve filters when opening file history. Attachment filenames use the existing UUID library to avoid simultaneous-upload collisions.

### Manual verification

1. Open **DeFlip Settings**, click and edit the terms, save, leave the page, and reopen it. Confirm the editor stays visible, the save message appears, and the text persists.
2. Switch between System Configuration and DeFlip Settings quickly. Only the final selected page should appear, without leftover library settings.
3. Open **DeFlip Access Report**. Apply a title/date filter, reset it, and open **View history** and **View all reader details**. Confirm Institution and Phone Number match the guest form/member profile and empty historical values show `-`. Compare total access with the history rows, then check print and spreadsheet output, including phone prefixes `0` and `+`.
4. Open a public PDF from its catalog record in a guest session. Check required fields, phone values beginning with `0` or `+`, terms, and the continuation flow. Check the form at a mobile width.
5. After continuation, confirm the book and toolbar fill the popup, page arrows work, and the download button follows its setting. Verify restricted attachments with the appropriate member account.
6. Confirm PDF attachments open in the catalog modal. On a disposable encrypted PDF, check correct, missing, and incorrect File Password values. Private attachments must be absent from the public catalog; members with a disallowed type must not receive PDF content from either stream URL. Check Link/Popup/Embed separately on a link or video attachment.

The original migration remains unchanged. The plugin version is now 1.1.1: SLiMS 9.8.0 requires re-enabling an upgraded plugin in **System > Plugins** to record its new version. Its migration runner skips migration 1 when that migration was already recorded. Back up the database, plugin, and eight host integration files before transferring this update to another installation.

---

We thank you to Mas @heroesoebekti for his initial effort in creating the wonderful plugin for SLiMS 9 Bulian called dflip. The plugin was created to make a flipbook reader in SLiMS 9 Bulian. Since a flipbook mode reader can be an eye-catching mode to reader, as if they read on a physical book.

After a while, we need to increase the pace of the game for this plugin. This latest plugin is not just a repackage plugin based on the first plugin created by Mas @heroesoebekti. But we made some improvements to this plugin instead of just repackaging it. Those improvements are:

- DeFlip Settings

The plugin, DeFlip, is having a settings! Yes, included in the settings are 1) Download restrictions, 2) Guest access mode, 3) Editor for terms and conditions. The settings' available within the System Module.

![Screenshot 2022-03-30 214038](https://user-images.githubusercontent.com/125229/160862437-9a0cf3cf-d4d4-4546-b4a7-82a01c8405bc.jpg)

- DeFlip Download Counter

DeFlip brings its own reporting sheet. Go to the Reporting Module and you will find a report called DeFlip Download Counter.

![Screenshot 2022-03-30 214112](https://user-images.githubusercontent.com/125229/160863091-09c980f1-59a0-4837-8c67-2f805b45fa0b.jpg)

![Screenshot 2022-03-30 214907](https://user-images.githubusercontent.com/125229/160863647-c182ccc2-456c-4938-bcee-1ca7bd68d800.jpg)

We hope you'll find joyful moments when using the plugin. Drop some words in Issue if you'll find anything need to be improved or fixed.

p.s.

You are responsible for any consequences caused by using this plugin. Use it with its own risk.
