<?php
// Simple XSS protection
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

// Handle status update
if (isset($_POST['update_status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $status = $_POST['status'];
    $conn->query("UPDATE bookings SET status = '$status' WHERE id = $booking_id");
    $success = "Booking status updated!";
}

// Get all bookings
$bookings = $conn->query("
    SELECT b.*, u.full_name, u.email, u.phone, s.name as service_name, s.price 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN services s ON b.service_id = s.id 
    ORDER BY b.booking_date DESC, b.booking_time DESC
");

$total_bookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0];
$pending_bookings = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetch_row()[0];
$total_customers = $conn->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetch_row()[0];
$total_revenue = $conn->query("SELECT SUM(price) FROM bookings b JOIN services s ON b.service_id = s.id WHERE status='completed'")->fetch_row()[0];
$completed_bookings = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='completed'")->fetch_row()[0];
$today_bookings = $conn->query("SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE()")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxe Saloon | Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            color: white;
            transition: all 0.3s;
            z-index: 100;
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h3 {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }

        .sidebar-header h3 i {
            margin-right: 10px;
            color: #f5b042;
        }

        .sidebar-header p {
            font-size: 12px;
            opacity: 0.7;
            margin-top: 8px;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 14px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            gap: 12px;
            font-weight: 500;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 3px solid #f5b042;
        }

        .sidebar-menu a i {
            width: 22px;
            font-size: 18px;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 25px 35px;
        }

        /* Top Navbar */
        .top-nav {
            background: white;
            border-radius: 20px;
            padding: 15px 25px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }

        .page-title h2 {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a2e;
            margin: 0;
        }

        .page-title p {
            font-size: 13px;
            color: #666;
            margin: 5px 0 0;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            border-radius: 24px;
            padding: 25px;
            transition: all 0.3s;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 55px;
            height: 55px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .stat-icon i {
            font-size: 28px;
            color: #667eea;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        .stat-change {
            font-size: 12px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .trend-up { color: #10b981; }
        .trend-down { color: #ef4444; }

        /* Action Cards */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .action-card {
            background: white;
            border-radius: 20px;
            padding: 0;
            overflow: hidden;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .action-info {
            padding: 20px;
        }

        .action-info h4 {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 5px;
            color: #1a1a2e;
        }

        .action-info p {
            font-size: 13px;
            color: #666;
            margin: 0;
        }

        .action-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 15px;
            border-radius: 18px;
        }

        .action-icon i {
            font-size: 28px;
            color: white;
        }

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .table-header h4 {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a2e;
            margin: 0;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 10px 15px 10px 40px;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            width: 250px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px 12px;
            color: #666;
            font-weight: 600;
            font-size: 13px;
            border-bottom: 1px solid #eee;
        }

        td {
            padding: 18px 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            color: #333;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending { background: #fef3c7; color: #d97706; }
        .status-confirmed { background: #d1fae5; color: #059669; }
        .status-completed { background: #dbeafe; color: #2563eb; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }

        select.status-select {
            padding: 6px 12px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            font-size: 13px;
            background: white;
            cursor: pointer;
        }

        @media (max-width: 1024px) {
            .sidebar { width: 80px; }
            .sidebar-header h3 span, .sidebar-header p, .sidebar-menu a span { display: none; }
            .sidebar-menu a { justify-content: center; padding: 15px; }
            .sidebar-menu a i { margin: 0; }
            .main-content { margin-left: 80px; }
        }

        @media (max-width: 768px) {
            .main-content { padding: 15px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 15px; }
            .action-grid { grid-template-columns: 1fr; }
            .table-container { padding: 15px; overflow-x: auto; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-spa"></i> <span>Luxe Saloon</span></h3>
            <p><span>Admin Control Panel</span></p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="active">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </a>
            <a href="bookings.php">
                <i class="fas fa-calendar-alt"></i> <span>Bookings</span>
            </a>
            <a href="services.php">
                <i class="fas fa-calendar-alt"></i> <span>Services</span>
            </a>
            <a href="backup.php">
                <i class="fas fa-database"></i> <span>Backup & Export</span>
            </a>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-nav">
            <div class="page-title">
                <h2>Dashboard</h2>
                <p>Welcome back to your salon management hub</p>
            </div>
            <div class="admin-info">
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                </div>
                <div>
                    <strong><?php echo escape($_SESSION['user_name']); ?></strong>
                    <small class="d-block text-muted">Administrator</small>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-value"><?php echo $total_bookings; ?></div>
                <div class="stat-label">Total Bookings</div>
                <div class="stat-change"><span class="trend-up"><i class="fas fa-arrow-up"></i> +12%</span> vs last month</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?php echo $pending_bookings; ?></div>
                <div class="stat-label">Pending Approval</div>
                <div class="stat-change"><span class="trend-up"><i class="fas fa-arrow-up"></i> +5%</span> vs last week</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-value"><?php echo $total_customers; ?></div>
                <div class="stat-label">Active Customers</div>
                <div class="stat-change"><span class="trend-up"><i class="fas fa-arrow-up"></i> +18%</span> vs last month</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="stat-value">RM <?php echo number_format($total_revenue ?? 0, 2); ?></div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-change"><span class="trend-up"><i class="fas fa-arrow-up"></i> +23%</span> vs last month</div>
            </div>
        </div>

        <!-- Secondary Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?php echo $completed_bookings; ?></div>
                <div class="stat-label">Completed Services</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-value"><?php echo $today_bookings; ?></div>
                <div class="stat-label">Today's Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-value">4.8</div>
                <div class="stat-label">Customer Rating</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-value"><?php echo $total_bookings + 20; ?>%</div>
                <div class="stat-label">Occupancy Rate</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="action-grid">
            <a href="services.php" class="action-card">
                <div class="action-info">
                    <h4><i class="fas fa-plus-circle me-2" style="color: #667eea;"></i> Add New Service</h4>
                    <p>Expand your service catalog</p>
                </div>
                <div class="action-icon"><i class="fas fa-cut"></i></div>
            </a>
            <a href="backup.php" class="action-card">
                <div class="action-info">
                    <h4><i class="fas fa-database me-2" style="color: #10b981;"></i> Backup Database</h4>
                    <p>Export all data for safe keeping</p>
                </div>
                <div class="action-icon"><i class="fas fa-download"></i></div>
            </a>
            <a href="#" class="action-card">
                <div class="action-info">
                    <h4><i class="fas fa-chart-bar me-2" style="color: #f59e0b;"></i> Generate Report</h4>
                    <p>View detailed analytics</p>
                </div>
                <div class="action-icon"><i class="fas fa-chart-pie"></i></div>
            </a>
        </div>

        <!-- Bookings Table -->
        <div class="table-container">
            <div class="table-header">
                <h4><i class="fas fa-list me-2"></i> All Bookings</h4>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search bookings..." onkeyup="searchTable()">
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table id="bookingsTable">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($b = $bookings->fetch_assoc()): ?>
                        <tr>
                            <td><span class="fw-bold"><?php echo $b['booking_no']; ?></span></td>
                            <td>
                                <div class="fw-bold"><?php echo escape($b['full_name']); ?></div>
                                <small class="text-muted"><?php echo $b['email']; ?></small>
                            </td>
                            <td><?php echo $b['service_name']; ?></td>
                            <td><?php echo date('d M Y', strtotime($b['booking_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($b['booking_time'])); ?></td>
                            <td><span class="fw-bold">RM <?php echo $b['price']; ?></span></td>
                            <td>
                                <span class="status-badge status-<?php echo $b['status']; ?>">
                                    <?php echo ucfirst($b['status']); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <option value="pending" <?php echo $b['status']=='pending'?'selected':''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $b['status']=='confirmed'?'selected':''; ?>>Confirm</option>
                                        <option value="completed" <?php echo $b['status']=='completed'?'selected':''; ?>>Complete</option>
                                        <option value="cancelled" <?php echo $b['status']=='cancelled'?'selected':''; ?>>Cancel</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function searchTable() {
            let input = document.getElementById('searchInput');
            let filter = input.value.toLowerCase();
            let table = document.getElementById('bookingsTable');
            let rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                let row = rows[i];
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            }
        }
    </script>
</body>
</html>