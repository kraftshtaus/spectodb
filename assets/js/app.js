document.addEventListener('DOMContentLoaded', () => {
    if (!window.dashboardData) {
        return;
    }

    const data = window.dashboardData;

    Chart.defaults.color = '#9a9a9a';
    Chart.defaults.borderColor = '#252525';

    new Chart(document.getElementById('eventChart'), {
        type: 'bar',
        data: {
            labels: data.eventLabels,
            datasets: [{
                label: 'Event count',
                data: data.eventValues,
                backgroundColor: '#ff5b2e',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: {
                        color: '#1f1f1f'
                    }
                },
                y: {
                    grid: {
                        color: '#1f1f1f'
                    }
                }
            }
        }
    });

    new Chart(document.getElementById('successChart'), {
        type: 'doughnut',
        data: {
            labels: ['Success', 'Failed'],
            datasets: [{
                data: [data.successCases, data.failedCases],
                backgroundColor: ['#ff5b2e', '#2a2a2a'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});