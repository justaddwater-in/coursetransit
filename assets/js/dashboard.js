document.addEventListener('DOMContentLoaded', function () {

    if (typeof Chart === 'undefined' || typeof CourseTransitDashboard === 'undefined') {
        return;
    }

    const labels = Object.keys(CourseTransitDashboard.ordersChart);
    const data = Object.values(CourseTransitDashboard.ordersChart);

    const canvas = document.getElementById('ordersChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    new Chart(ctx, {
        type: 'line',

        data: {
            labels: labels,

            datasets: [{
                label: 'Orders',
                data: data,

                backgroundColor: 'rgba(0, 123, 255, 0.08)',
                borderColor: '#007bff',
                borderWidth: 3,

                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#007bff',

                tension: 0.35,
                fill: true
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: true
                },

                tooltip: {
                    backgroundColor: '#1f2937',

                    callbacks: {
                        label: function(context) {
                            return ' Orders: ' + context.parsed.y;
                        }
                    }
                }
            },

            scales: {
                y: {
                    beginAtZero: true,

                    ticks: {
                        color: '#6b7280'
                    },

                    grid: {
                        color: '#eef2f7'
                    }
                },

                x: {
                    ticks: {
                        color: '#6b7280',
                        maxRotation: 0
                    },

                    grid: {
                        color: '#eef2f7'
                    }
                }
            }
        }
    });

});
document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('ct-setup-modal');
    if (!modal) return;

    modal.style.display = 'block';
    document.body.classList.add('ct-modal-open');

    const fixBtn = document.getElementById('ct-auto-fix');

    if (!fixBtn) {
        console.log('Fix button not found');
        return;
    }

    fixBtn.addEventListener('click', function () {

        console.log('Fix clicked');

        fixBtn.disabled = true;
        fixBtn.innerText = 'Fixing...';

        fetch(CourseTransitAjax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'coursetransit_fix_wc_settings'
            })
        })
            .then(res => res.json())
            .then(res => {

                console.log(res);

                if (res.success) {

                    fixBtn.innerText = 'Done';

                    // 1. Convert all items to success
                    document.querySelectorAll('.ct-checklist li').forEach(li => {
                        li.classList.remove('bad');
                        li.classList.add('ok');

                        const icon = li.querySelector('.ct-icon');
                        if (icon) icon.innerText = '✔️';
                    });

                    // 2. Update heading
                    const heading = document.querySelector('.ct-card h3');
                    if (heading) heading.innerText = 'Setup Complete';

                    // 3. Update help text
                    const help = document.querySelector('.ct-help');
                    if (help) {
                        help.innerHTML = 'Your WooCommerce setup is now complete. You’re ready to sell and grant course access.';
                    }

                    // 4. Change button to Close
                    fixBtn.disabled = false;
                    fixBtn.innerText = 'Close';

                    fixBtn.onclick = closeModal;

                    // 5. Enable click outside
                    enableBackdropClose();
                } else {
                    alert(res.data || 'Something went wrong');
                    fixBtn.disabled = false;
                    fixBtn.innerText = 'Fix Automatically';
                }

            })
            .catch(err => {
                console.error(err);
                alert('AJAX failed');
                fixBtn.disabled = false;
            });

    });

});
function closeModal() {
    const modal = document.getElementById('ct-setup-modal');
    modal.style.display = 'none';
    document.body.classList.remove('ct-modal-open');
}

function enableBackdropClose() {
    const modal = document.getElementById('ct-setup-modal');

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });
}