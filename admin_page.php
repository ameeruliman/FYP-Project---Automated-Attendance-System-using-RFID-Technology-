<?php
session_start();

// Check if the user is logged in and is either admin or headmaster
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin', 'headmaster'])) {
    header("Location: loginPage.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Add Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Add Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            color: #1e293b;
        }

        header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #fff;
            width: 100%;
            padding: 25px 0;
            text-align: center;
            position: relative;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 70% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        header h1 {
            font-size: 2.5em;
            font-weight: 700;
            margin: 0;
            position: relative;
            display: inline-block;
            background: linear-gradient(90deg, #fff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        header h1::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #34c759);
            border-radius: 3px;
        }

        main {
            width: 95%;
            max-width: 1400px;
            padding: 30px;
            margin: 0 auto;
        }

        .welcome-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            padding: 25px 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #34c759, #f43f5e);
            border-radius: 4px 4px 0 0;
        }

        .welcome-section h2 {
            color: #1e293b;
            font-size: 1.8em;
            margin: 0 0 10px 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .welcome-section h2::before {
            content: '👋';
            font-size: 1.2em;
        }

        .welcome-section p {
            color: #64748b;
            margin: 0;
            line-height: 1.6;
            font-size: 1.1em;
            max-width: 800px;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            margin-top: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .chart-column {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 25px;
            height: auto;
            transition: all 0.3s ease;
        }

        .chart-container {
            margin-bottom: 25px;
            padding: 20px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            max-width: 450px;
            margin-left: auto;
            margin-right: auto;
            height: 300px;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        canvas {
            max-width: 100%;
            margin: 10px auto;
            height: 250px !important;
            display: block;
        }

        .chart-container h4 {
            margin: 0 0 15px 0;
            font-size: 1.1em;
            color: #1e293b;
            text-align: center;
            font-weight: 600;
            width: 100%;
        }

        @media (max-width: 1024px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }

            .chart-container {
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .filter-container {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-container input[type="date"],
            .filter-container button {
                width: 100%;
            }
        }

        .card-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .card {
            background: linear-gradient(135deg, var(--start-color) 0%, var(--end-color) 100%);
            border-radius: 16px;
            padding: 25px;
            text-align: left;
            cursor: pointer;
            transition: all 0.3s ease;
            height: auto;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            text-decoration: none;
        }

        /* Add this new style for logout card */
        .logout-card {
            min-height: 80px !important;
            padding: 15px !important;
            text-align: center;
            justify-content: center;
            align-items: center;
            flex-direction: row;
            gap: 10px;
        }

        .logout-card .material-icons {
            margin-bottom: 0 !important;
        }

        .logout-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(220, 38, 38, 0.2);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .card .material-icons {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: rgba(255, 255, 255, 0.9);
        }

        .card p.tip {
            font-size: 1.3em;
            font-weight: 600;
            color: #fff;
            margin: 0;
        }

        .card p.second-text {
            font-size: 0.95em;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 8px;
        }

        .red { --start-color: #f43f5e; --end-color: #e11d48; }
        .blue { --start-color: #3b82f6; --end-color: #2563eb; }
        .green { --start-color: #34c759; --end-color: #22a344; }

        .chart-column {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 25px;
            height: auto;
        }

        .filter-container {
            display: flex;
            gap: 15px;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .filter-container input[type="date"] {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
        }

        .filter-container button {
            background: #1e2a38;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .filter-container button:hover {
            background: #2d3a4e;
        }

        .chart-title {
            font-size: 1.5em;
            color: #1e293b;
            margin-bottom: 20px;
            text-align: left;
        }

        .btn {
            text-align: right;
            margin-top: 30px;
        }

        .button1 {
            background: linear-gradient(135deg, #1e2a38 0%, #2d3a4e 100%);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1.1em;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .button1:hover {
            background: linear-gradient(135deg, #2d3a4e 0%, #1e2a38 100%);
            transform: translateY(-2px);
        }

        canvas {
            max-width: 100%;
            margin: 20px 0;
        }

        .chart-container {
            margin-bottom: 30px;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            max-width: 600px;  /* Add this line to make charts smaller */
            margin-left: auto;  /* Center the charts */
            margin-right: auto;  /* Center the charts */
        }

        .card p.second-text {
            font-size: 0.95em;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 8px;
            text-decoration: none;  /* Add this line to remove underline */
        }

        .card {
            background: linear-gradient(135deg, var(--start-color) 0%, var(--end-color) 100%);
            border-radius: 16px;
            padding: 25px;
            text-align: left;
            cursor: pointer;
            transition: all 0.3s ease;
            height: auto;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            text-decoration: none;  /* Add this line to remove underline */
        }
    </style>
</head>
<body>
<header>
    <h1>Admin Dashboard</h1>
</header>

<main>
    <div class="welcome-section">
        <h2>Welcome, <?php echo ucfirst($_SESSION['role']); ?>!</h2>
        <p>You have successfully logged in as a <?php echo $_SESSION['role']; ?>. Here is your panel where you can manage users, view attendance, etc.</p>
    </div>

    <div class="dashboard-container">
        <div class="card-column">
            <a href="attendance_record.php" class="card red">
                <span class="material-icons">event_available</span>
                <div>
                    <p class="tip">View Attendance</p>
                    <p class="second-text">Click to view staff attendance records</p>
                </div>
            </a>

            <a href="admin_approve_emergency.php" class="card green">
                <span class="material-icons">emergency</span>
                <div>
                    <p class="tip">Emergency Information</p>
                    <p class="second-text">Click to view emergency info by staff</p>
                </div>
            </a>

            <a href="admin_approve_leave.php" class="card blue">
                <span class="material-icons">event_busy</span>
                <div>
                    <p class="tip">Leave Application</p>
                    <p class="second-text">Click to view leave applications</p>
                </div>
            </a>

            <a href="registered_staff.php" class="card" style="--start-color: #9333ea; --end-color: #7e22ce;">
                <span class="material-icons">people</span>
                <div>
                    <p class="tip">Registered Staff</p>
                    <p class="second-text">Click to view all registered staff</p>
                </div>
            </a>

            <a href="logout.php" class="card logout-card" style="--start-color: #dc2626; --end-color: #b91c1c;">
                <span class="material-icons" style="font-size: 1.5em; margin-bottom: 5px;">logout</span>
                <div>
                    <p class="tip" style="font-size: 1.1em;">Logout</p>
                </div>
            </a>
        </div>

        <div class="chart-column">
            <h3 class="chart-title">Attendance Pattern</h3>

            <div class="filter-container">
                <label for="fromDate">From:</label>
                <input type="date" id="fromDate">
                <label for="toDate">To:</label>
                <input type="date" id="toDate">
                <button onclick="filterCharts()">Apply Filter</button>
            </div>

            <div class="chart-container">
                <canvas id="attendanceChart"></canvas>
            </div>
            
            <div class="chart-container">
                <h4>Staff Attendance Distribution</h4>
                <canvas id="absentPieChart"></canvas>
            </div>

            <div class="chart-container">
                <canvas id="absentChart"></canvas>
            </div>
        </div>
    </div>
</main>

<script>
let attendanceChart;
let absentChart;
let newAbsentChart;
let absentPieChart;

function loadCharts(from = null, to = null) {
    let presentURL = 'get_attendance_data.php';
    let absentURL = 'get_absent_chart_data.php';
    
    if (from && to) {
        presentURL += `?from=${from}&to=${to}`;
        absentURL += `?from=${from}&to=${to}`;
    }

    // Load Present Chart
    fetch(presentURL)
        .then(res => res.json())
        .then(data => {
            const labels = data.map(item => item.date);
            const counts = data.map(item => item.count);

            const ctx = document.getElementById('attendanceChart').getContext('2d');
            if (attendanceChart) attendanceChart.destroy();

            attendanceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Number of Staff Present',
                        data: counts,
                        backgroundColor: '#3b82f6'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });
        });

    // Load Absent Data
    fetch(absentURL)
        .then(res => res.json())
        .then(data => {
            const labels = data.map(item => item.date);
            const counts = data.map(item => item.count);
            
            // Calculate totals for pie chart
            const totalAbsent = counts.reduce((sum, count) => sum + count, 0);
            const totalPresent = data.length * 10 - totalAbsent; // Assuming 10 total staff

            // Create Absent Bar Chart
            const barCtx = document.getElementById('absentChart').getContext('2d');
            if (absentChart) absentChart.destroy();

            absentChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Number of Staff Absent',
                        data: counts,
                        backgroundColor: '#f43f5e'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });

            // Create Pie Chart
            const pieCtx = document.getElementById('absentPieChart').getContext('2d');
            if (absentPieChart) absentPieChart.destroy();

            absentPieChart = new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: ['Present', 'Absent'],
                    datasets: [{
                        data: [totalPresent, totalAbsent],
                        backgroundColor: ['#3b82f6', '#f43f5e']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });
}

function filterCharts() {
    const from = document.getElementById('fromDate').value;
    const to = document.getElementById('toDate').value;

    if (from && to) {
        loadCharts(from, to);
    } else {
        alert("Please select both dates.");
    }
}

// Load charts when page loads
loadCharts();
</script>

</body>
</html>
