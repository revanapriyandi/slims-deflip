(function () {
    'use strict';

    var frame = document.getElementById('reportView');
    if (!frame) return;
    var observer;
    function sizeResults() {
        if (observer) observer.disconnect();
        var content = frame.contentDocument && frame.contentDocument.getElementById('pageContent');
        if (!content) return;
        function resize() { frame.style.height = Math.max(220, content.scrollHeight + 32) + 'px'; }
        resize();
        if (window.ResizeObserver) {
            observer = new ResizeObserver(resize);
            observer.observe(content);
        }
    }
    frame.addEventListener('load', sizeResults);
    sizeResults();
}());
