document.addEventListener('DOMContentLoaded', function () {
    const miniCtx = document.getElementById('miniCompletionChart');
    if (miniCtx) {
        new Chart(miniCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    data: [72, 75, 71, 82, 79, 88, 87],
                    borderColor: '#7DD3FC',
                    backgroundColor: 'rgba(96, 165, 250, 0.35)',
                    fill: true,
                    tension: 0.45,
                    pointRadius: 0,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true }
                },
                scales: {
                    x: { display: false },
                    y: { display: false }
                }
            }
        });
    }

    const reportCtx = document.getElementById('reportStatusChart');
    if (reportCtx) {
        new Chart(reportCtx, {
            type: 'doughnut',
            data: {
                labels: ['Submitted', 'Pending', 'Late', 'Reviewed'],
                datasets: [{
                    data: [56, 18, 12, 14],
                    backgroundColor: ['#5B3E8E', '#F59E0B', '#EF4444', '#22C55E'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 16
                        }
                    }
                }
            }
        });
    }

    // User dropdown
    const userToggle = document.getElementById('userDropdownToggle');
    const userMenu = document.getElementById('userDropdownMenu');

    if (userToggle && userMenu) {
        userToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            userMenu.classList.toggle('show');
        });
    }

    // Top nav dropdown
    const navDropdowns = document.querySelectorAll('.nav-dropdown');

    navDropdowns.forEach(function (dropdown) {
        const btn = dropdown.querySelector('.nav-dropdown-toggle');

        if (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();

                navDropdowns.forEach(function (item) {
                    if (item !== dropdown) {
                        item.classList.remove('open');
                    }
                });

                dropdown.classList.toggle('open');
            });
        }
    });

    // Close all dropdowns when clicking outside
    document.addEventListener('click', function (e) {
        if (userMenu && userToggle) {
            if (!userMenu.contains(e.target) && !userToggle.contains(e.target)) {
                userMenu.classList.remove('show');
            }
        }

        navDropdowns.forEach(function (dropdown) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });
    });
});