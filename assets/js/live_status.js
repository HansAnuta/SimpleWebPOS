(function () {
    async function pollStatus() {
        // NEW: Do not attempt to ping the server if we are offline
        if (!navigator.onLine) return; 

        try {
            const res = await fetch('api/check_status.php');
            const data = await res.json();

            if (data.status === 'suspended' || data.status === 'unauthorized') {
                window.location.href = 'suspended.php';
                return;
            }

            if (data.expired) {
                window.location.href = 'landing.php?expired=true';
                return;
            }

            const warningModal = document.getElementById('live-warning-modal');
            if (data.warning && warningModal && !warningModal.classList.contains('show')) {
                const warningText = document.getElementById('live-warning-text');
                if (warningText) {
                    warningText.innerText = data.warning;
                }
                warningModal.classList.add('show');
            }
        } catch (error) {
            // Only log the error if the browser thinks it's online but still fails
            if (navigator.onLine) console.error('Real-time sync error:', error);
        }
    }

    async function dismissLiveWarning() {
        const warningModal = document.getElementById('live-warning-modal');
        if (warningModal) {
            warningModal.classList.remove('show');
        }
        await fetch('api/clear_warning.php');
    }

    window.addEventListener('DOMContentLoaded', () => {
        const dismissBtn = document.getElementById('live-warning-dismiss');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', dismissLiveWarning);
        }
        setInterval(pollStatus, 10000);
    });
})();
