(function () {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('expired')) {
        const banner = document.getElementById('expired-banner');
        if (banner) {
            banner.style.display = 'block';
        }
    }
})();
