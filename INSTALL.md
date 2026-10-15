# Installing DeFlip 1.2.0

DeFlip 1.2.0 installs by copying this plugin directory only. SLiMS core files stay unmodified, so later SLiMS upgrades do not have to merge DeFlip changes. Tested against the stock SLiMS 9.8.0 core files.

Review [THIRD_PARTY.md](THIRD_PARTY.md) first: DearFlip Lite v1.7 has a non-commercial license.

## Install or upgrade

1. Back up the database and the existing `plugins/deflip/` directory.
2. Copy this directory to `plugins/deflip/` on the server. The `integration/` and `tools/` folders are not needed at runtime; leave them out of production.
3. In **System > System Configuration**, set the PDF viewer to **PDF.js** (DeFlip takes over that viewer).
4. Open **System > Plugins** and enable **DearFlip**. After an upgrade SLiMS shows the plugin as disabled because its version changed; enable it again. Settings and guest/access records are kept.
5. Open **System > DeFlip Settings** and review guest registration, terms, and the download button. Clear OPcache/asset caches if the server uses them.

## How it works without core changes

| Behavior | Hook used (stock SLiMS) |
| --- | --- |
| Flipbook viewer, guest form, access counting | `fstream_pdf_before_download` |
| Encrypted PDF password loader | plugin registers its own loader key in the session |
| Guest registration and member-type checks before `p=fstream-pdf` streams bytes (stock SLiMS keeps streaming after its redirect header) | `before_content_load` |
| Only PDF loader keys can be read through `p=fstream&initialize=true` (stock returns any session value) | `before_content_load` |
| XLSX export keeps reader text as text and phone prefixes such as `0`/`+62` | plugin's own export, `Reporting > DeFlip Access Report` |

## Database migration

Activation runs `migration/1_CreateAndAlter.php` once: it adds nullable `files_read.guest_id` and creates `files_read_guest`. The database account needs `ALTER`/`CREATE` during activation. Installations that already recorded migration 1 skip it. `down()` is empty; disabling the plugin keeps the data.

## Known limits on stock SLiMS

- **File Password:** stock `pop_attach.php` trims and strips tags from the password. Passwords with leading/trailing spaces or `<`/`>` will not unlock the PDF; choose a password without them.
- **Mobile:** the stock catalog opens PDFs in a new tab on mobile instead of the modal. The DeFlip reader still loads.
- **Direct file URLs:** stock SLiMS does not block direct requests to `repository/*.pdf`. To force every read through DeFlip, add a rule in the web server configuration (not a SLiMS file). Apache virtual host:

  ```apache
  <LocationMatch "(?i)^/repository/.*\.pdf$">
      Require all denied
  </LocationMatch>
  ```

  Nginx:

  ```nginx
  location ~* ^/repository/.*\.pdf$ {
      return 403;
  }
  ```

  Adjust the path when SLiMS lives in a subdirectory.

The download setting hides the toolbar button; it is not DRM. File Password unlocks an already encrypted PDF; it does not encrypt the file.

## Acceptance checks

1. Guest session: open a PDF from the catalog, check the form errors, register, and confirm the reader loads.
2. Open `index.php?p=fstream-pdf&fid=<id>&bid=<id>` in a fresh guest session: it must return 403 while guest registration is enabled.
3. Restrict an attachment to a member type: a guest and a member of another type must not receive PDF bytes from `p=fstream` or `p=fstream-pdf`.
4. Encrypted PDF with correct, missing, and wrong File Password.
5. Report filters, history, print, and **Export Spreadsheet**; open the XLSX and check phone numbers.

## Rollback

Disable DearFlip, restore the previous `plugins/deflip/` directory, and enable it again. Do not drop the guest/history tables by hand.

## Optional core patch

`integration/` holds the earlier 1.1.1 core patch for SLiMS 9.8.0 (stricter attachment form validation, private cache headers, admin AJAX race fix, `repository/.htaccess` PDF block). It is not required by 1.2.0.
