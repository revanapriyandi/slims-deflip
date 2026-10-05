# Attribution and third-party licenses

DeFlip's PHP integration and host changes follow the SLiMS GPL v3 license in `LICENSE`. Bundled third-party libraries keep their own licensing terms; the root GPL license does not relicense them.

- Original DeFlip/SLiMS plugin: Heru Subekti, Drajat Hasan, and Arif Syamsudin. Upstream: [drajathasan/deflip](https://github.com/drajathasan/deflip).
- DearFlip 1.7.3.5: Deepak Ghimire / DearHive. The [upstream Lite v1.7 repository](https://github.com/dearhive/dearflip-js-flipbook) states **CC BY-NC-ND 4.0** and limits the Lite edition to non-commercial use. Its upstream license is retained in `viewer/LICENSE-DearFlip`. The bundled DearFlip assets have not been modified by this maintenance release. Commercial deployment requires an appropriate license from the vendor; public availability of this repository does not grant commercial rights.
- PDF.js 2.3.200: Mozilla and contributors, Apache License 2.0. See `viewer/js/libs/LICENSE-PDFjs` and [mozilla/pdf.js](https://github.com/mozilla/pdf.js). CMap license notices remain in `viewer/js/libs/cmaps/LICENSE`.
- jQuery 1.11.0: jQuery Foundation, MIT license. Its copyright/license notice is retained in the bundled file.
- Three.js, Mockup, Themify icons, and the remaining viewer assets retain their original upstream notices. Refer to the corresponding files and upstream owners for their terms.

This release updates DeFlip's wrapper, forms, reports, and host integration. It applies the PDF.js mitigation through document-loading options without modifying the bundled minified DearFlip or PDF.js source. Review the applicable licenses for the intended deployment.
