(function ($) {
    'use strict';

    var container = document.getElementById('deflip-reader');
    var status = document.getElementById('deflip-reader-status');
    var passwordFailure = false;
    if (!container) return;

    function showFailure(error) {
        var passwordResponses = window.pdfjsLib && window.pdfjsLib.PasswordResponses;
        if (error && (error.name === 'PasswordException' || (passwordResponses &&
            (error.code === passwordResponses.NEED_PASSWORD || error.code === passwordResponses.INCORRECT_PASSWORD)))) {
            passwordFailure = true;
        }
        status.hidden = false;
        status.textContent = status.getAttribute(passwordFailure ? 'data-password-label' : 'data-error-label');
        status.setAttribute('role', 'alert');
    }

    if (!$ || !$.fn.flipBook) { showFailure(); return; }

    $(function () {
        function readerHeight() {
            return window.innerHeight;
        }
        var loader = container.getAttribute('data-loader');
        if (loader && !window.ObjectPdf) { showFailure(); return; }
        var parameters = loader && window.ObjectPdf ? window.ObjectPdf.create(loader) : Promise.resolve({});
        parameters.then(function (documentParameters) {
            var defaults = window.DFLIP.defaults;
            documentParameters.url = container.getAttribute('data-source');
            documentParameters.rangeChunkSize = defaults.rangeChunkSize;
            documentParameters.cMapUrl = defaults.cMapUrl;
            documentParameters.cMapPacked = true;
            documentParameters.imageResourcesPath = defaults.imageResourcesPath;
            documentParameters.disableAutoFetch = true;
            documentParameters.disableStream = true;
            documentParameters.disableFontFace = defaults.disableFontFace;
            // Mozilla's workaround for CVE-2024-4367 in the bundled PDF.js.
            documentParameters.isEvalSupported = false;
            var book = $(container).flipBook(container.getAttribute('data-source'), {
                height: readerHeight(),
                webgl: true,
                backgroundColor: '#f4f6f9',
                enableDownload: container.getAttribute('data-download') === 'true',
                docParameters: documentParameters,
                soundEnable: false,
                onReady: function () { status.hidden = true; }
            });
            window.addEventListener('resize', function () {
                book.options.height = readerHeight();
                book.resize();
            });
            window.addEventListener('offline', showFailure);
            var readyCheck = window.setInterval(function () {
                if (book.contentProvider && book.contentProvider.pdfDocument) {
                    window.clearInterval(readyCheck);
                } else if (book.contentProvider && book.contentProvider.loading && book.contentProvider.loading.promise) {
                    var loading = book.contentProvider.loading;
                    loading.promise.catch(showFailure);
                    window.clearInterval(readyCheck);
                }
            }, 250);
            window.setTimeout(function () { window.clearInterval(readyCheck); }, 15000);
        }).catch(showFailure);
    });
}(window.jQuery));
