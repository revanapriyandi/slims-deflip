# DeFlip for SLiMS

## DeFlip 1.2.0

Install by copying this directory to `plugins/deflip/`; SLiMS core files stay unmodified, so SLiMS upgrades need no DeFlip merge. See [INSTALL.md](INSTALL.md) for installation, known limits on stock SLiMS, and acceptance checks. Verified against stock SLiMS 9.8.0 core files; run the acceptance checks on staging before production.

Plugin/host code uses GPL v3. Bundled viewer libraries have their own terms, including DearFlip Lite's non-commercial restriction. See [THIRD_PARTY.md](THIRD_PARTY.md) before deployment.

DeFlip uses the existing SLiMS settings, authentication, report grid, CKEditor, CSRF support, and HTMLPurifier. No additional package or database migration is required for an installation with the original DeFlip migration already applied.

- **System > DeFlip Settings** manages guest registration, the reader download button, and terms and conditions. Rich text has a textarea fallback if CKEditor cannot start. Values are validated and terms are sanitized before storage and display.
- **Reporting > DeFlip Access Report** counts document openings. Repeated openings count separately; this is not a count of completed downloads or reading sessions. Date, bibliography, pagination, history, and spreadsheet filters use the same query criteria. A file linked to several collections contributes one row per access log; its displayed collection is the first linked bibliography ID.
- **View all reader details** lists every access record across collections. Both this view and **View history** show Reader, Institution, Phone Number, IP address, and Date alongside the document. Guest details come from the guest form; member details come from the member profile. Missing values appear as `-`. Print and spreadsheet output include the same columns; the DeFlip history spreadsheet stores values as text to preserve phone prefixes and prevent reader text being interpreted as formulas.
- **Print Current Page** produces an A4 landscape report with library identity, period, active filters, generation time, record count, and pagination context. Additional category/report headings are omitted. Semantic headers repeat across printed pages. It prints the currently displayed report page; export includes all records matching the filters.
- The guest popup contains only the reader form, terms, and continuation button. The reader displays only the book and its toolbar and fills the popup viewport.
- Hiding the download button changes the reader interface. PDF content is still delivered to the browser for reading.
- PDF attachments open in the catalog modal on desktop and in a new tab on mobile (stock SLiMS catalog behavior).
- **File Password** supplies the password for an already encrypted PDF through the SLiMS session loader. Stock SLiMS trims and strips tags from this field, so avoid leading/trailing spaces and `<`/`>` in PDF passwords. It does not encrypt an unprotected document. A missing or incorrect password produces an access message in the reader. Public/Private and Member Type restrictions are checked before loading the viewer and again before streaming the PDF; malformed member limits deny access.
- The viewer passes `isEvalSupported: false` to PDF.js, applying Mozilla's documented workaround for [CVE-2024-4367](https://github.com/mozilla/pdf.js/security/advisories/GHSA-wgrm-67xf-hhpq). The bundled DearFlip/PDF.js versions remain unchanged; this mitigation does not claim a complete dependency security audit or compatibility with a newer PDF.js major version.

### Structure

`src/Settings.php`, `src/GuestAccess.php`, and `src/AccessReport.php` hold settings validation, guest registration, and report queries. `src/ReportGrid.php` extends the existing SLiMS grid with bounded page numbers, fixed report ordering, and semantic table headers. `pages/` handles SLiMS entry points; `views/` renders the guest form, filters, and print metadata. `assets/` contains scoped styles and scripts; `viewer/` retains the bundled DearFlip libraries.

Version 1.2.0 moves the 1.1.1 core changes into plugin hooks: guest registration and member-type checks run before `p=fstream-pdf` streams bytes, the PDF password loader only exposes its own session keys, and the plugin writes its own XLSX export with reader text kept as text. Direct `repository/*.pdf` requests need a web server rule; see [INSTALL.md](INSTALL.md). Reports accept 1–200 rows per page, recover stale pages, ignore unconfigured sort selectors, and preserve filters when opening file history.

### Manual verification

1. Open **DeFlip Settings**, click and edit the terms, save, leave the page, and reopen it. Confirm the editor stays visible, the save message appears, and the text persists.
2. Switch between System Configuration and DeFlip Settings quickly. Only the final selected page should appear, without leftover library settings.
3. Open **DeFlip Access Report**. Apply a title/date filter, reset it, and open **View history** and **View all reader details**. Confirm Institution and Phone Number match the guest form/member profile and empty historical values show `-`. Compare total access with the history rows, then check print and spreadsheet output, including phone prefixes `0` and `+`.
4. Open a public PDF from its catalog record in a guest session. Check required fields, phone values beginning with `0` or `+`, terms, and the continuation flow. Check the form at a mobile width.
5. After continuation, confirm the book and toolbar fill the popup, page arrows work, and the download button follows its setting. Verify restricted attachments with the appropriate member account.
6. On a disposable encrypted PDF, check correct, missing, and incorrect File Password values. Private attachments must be absent from the public catalog; members with a disallowed type must not receive PDF content from either stream URL. Check Link/Popup/Embed separately on a link or video attachment.

The original migration remains unchanged. After upgrading, re-enable the plugin in **System > Plugins** to record version 1.2.0; migration 1 is skipped when already recorded.

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
