// Chart configuration and handling

// Maximum data points to show on chart to prevent performance issues
const MAX_DATA_POINTS = 20;

// Initialize chart context and configuration
let chartContext;
let temperatureHumidityChart;

// Initialize the chart when DOM content is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get the chart canvas element
    chartContext = document.getElementById('chart').getContext('2d');
    
    // Create the chart
    temperatureHumidityChart = new Chart(chartContext, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Suhu (°C)',
                    data: [],
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderWidth: 2,
                    tension: 0.2,
                    fill: false
                },
                {
                    label: 'Kelembaban (%)',
                    data: [],
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderWidth: 2,
                    tension: 0.2,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Waktu'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Nilai'
                    },
                    beginAtZero: true,
                    suggestedMax: 100 // Kelembaban maksimal 100%
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Monitoring Suhu dan Kelembaban Real-time',
                    font: {
                        size: 16
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                },
                legend: {
                    position: 'top',
                }
            },
            animation: {
                duration: 500
            }
        }
    });
});

/**
 * Update chart with new temperature and humidity data
 * @param {Date|string} time - Current time or time string
 * @param {number} temperature - Temperature in Celsius
 * @param {number} humidity - Humidity percentage
 */
function updateChart(time, temperature, humidity) {
    // Format time if it's a Date object
    const timeLabel = time instanceof Date ? 
        time.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'}) : 
        time;
    
    // Add new data
    temperatureHumidityChart.data.labels.push(timeLabel);
    temperatureHumidityChart.data.datasets[0].data.push(temperature);
    temperatureHumidityChart.data.datasets[1].data.push(humidity);
    
    // Remove old data if exceeding maximum points
    if (temperatureHumidityChart.data.labels.length > MAX_DATA_POINTS) {
        temperatureHumidityChart.data.labels.shift();
        temperatureHumidityChart.data.datasets[0].data.shift();
        temperatureHumidityChart.data.datasets[1].data.shift();
    }
    
    // Update the chart
    temperatureHumidityChart.update();
}

/**
 * Clear all data from the chart
 */
function clearChart() {
    temperatureHumidityChart.data.labels = [];
    temperatureHumidityChart.data.datasets.forEach(dataset => {
        dataset.data = [];
    });
    temperatureHumidityChart.update();
}

/**
 * Set chart visibility
 * @param {boolean} visible - Whether to show or hide the chart
 */
function setChartVisibility(visible) {
    document.getElementById('chart').style.display = visible ? 'block' : 'none';
}