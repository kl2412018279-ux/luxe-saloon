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
    $success = "Booking status updated successfully!";
}

// Handle delete booking
if (isset($_GET['delete'])) {
    $booking_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM bookings WHERE id = $booking_id");
    $success = "Booking deleted successfully!";
}

// Filter by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "
    SELECT b.*, u.full_name, u.email, u.phone, s.name as service_name, s.price, s.duration, s.image_icon
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN services s ON b.service_id = s.id 
";

if ($status_filter != 'all') {
    $query .= " WHERE b.status = '$status_filter'";
}

if (!empty($search)) {
    $query .= (strpos($query, 'WHERE') !== false ? " AND " : " WHERE ") . " (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%' OR b.booking_no LIKE '%$search%')";
}

$query .= " ORDER BY b.booking_date DESC, b.booking_time DESC";

$bookings = $conn->query($query);

// Get counts for filters
$total_all = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0];
$total_pending = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetch_row()[0];
$total_confirmed = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetch_row()[0];
$total_completed = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='completed'")->fetch_row()[0];
$total_cancelled = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='cancelled'")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxe Salon | Manage Bookings</title>
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

        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 10px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
            color: #666;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .filter-tab:hover, .filter-tab.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateY(-2px);
        }

        .filter-tab .count {
            background: rgba(0,0,0,0.1);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 8px;
        }

        .filter-tab.active .count {
            background: rgba(255,255,255,0.2);
        }

        /* Search Section */
        .search-section {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
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
            vertical-align: middle;
        }

        .booking-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .booking-icon {
            width: 45px;
            height: 45px;
            background: rgba(102,126,234,0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .booking-icon i {
            font-size: 22px;
            color: #667eea;
        }

        .booking-info .booking-no {
            font-weight: 700;
            color: #1a1a2e;
        }

        .booking-info .booking-service {
            font-size: 12px;
            color: #888;
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
            padding: 8px 12px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            font-size: 13px;
            background: white;
            cursor: pointer;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-edit {
            background: #e0e7ff;
            color: #4338ca;
        }

        .btn-edit:hover {
            background: #4338ca;
            color: white;
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background: #dc2626;
            color: white;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #059669;
            border-left: 4px solid #059669;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 60px;
            color: #ccc;
            margin-bottom: 20px;
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
            .table-container { padding: 15px; overflow-x: auto; }
            .filter-tabs { justify-content: center; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-spa"></i> <span>Luxe Salon</span></h3>
            <p><span>Admin Control Panel</span></p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </a>
            <a href="bookings.php" class="active">
                <i class="fas fa-calendar-alt"></i> <span>Bookings</span>
            </a>
            <a href="backup.php">
                <i class="fas fa-database"></i> <span>Backup & Export</span>
            </a>
            <a href="services.php">
                <i class="fas fa-database"></i> <span>Services</span>
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
                <h2>Manage Bookings</h2>
                <p>View, update, and manage all customer appointments</p>
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

        <!-- Success Message -->
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?status=all" class="filter-tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                All <span class="count"><?php echo $total_all; ?></span>
            </a>
            <a href="?status=pending" class="filter-tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                Pending <span class="count"><?php echo $total_pending; ?></span>
            </a>
            <a href="?status=confirmed" class="filter-tab <?php echo $status_filter == 'confirmed' ? 'active' : ''; ?>">
                Confirmed <span class="count"><?php echo $total_confirmed; ?></span>
            </a>
            <a href="?status=completed" class="filter-tab <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">
                Completed <span class="count"><?php echo $total_completed; ?></span>
            </a>
            <a href="?status=cancelled" class="filter-tab <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">
                Cancelled <span class="count"><?php echo $total_cancelled; ?></span>
            </a>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <form method="GET" action="">
                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                    <input type="text" name="search" placeholder="Search by customer name, email or booking ID..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
            <?php if(!empty($search)): ?>
                <a href="?status=<?php echo $status_filter; ?>" class="btn btn-secondary">Clear Search</a>
            <?php endif; ?>
        </div>

        <!-- Bookings Table -->
        <div class="table-container">
            <div class="table-header">
                <h4><i class="fas fa-list me-2"></i> Booking List</h4>
                <span class="text-muted"><?php echo $bookings->num_rows; ?> bookings found</span>
            </div>
            
            <?php if($bookings->num_rows == 0): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h5>No Bookings Found</h5>
                    <p class="text-muted">There are no bookings in this category.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Booking Info</th>
                                <th>Customer</th>
                                <th>Date & Time</th>
                                <th>Service</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($b = $bookings->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="booking-cell">
                                        <div class="booking-icon">
                                            <i class="fas fa-ticket-alt"></i>
                                        </div>
                                        <div class="booking-info">
                                            <div class="booking-no"><?php echo $b['booking_no']; ?></div>
                                            <div class="booking-service"><?php echo date('d M Y', strtotime($b['created_at'])); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo escape($b['full_name']); ?></div>
                                    <small class="text-muted"><?php echo $b['email']; ?></small>
                                    <br><small class="text-muted"><?php echo $b['phone']; ?></small>
                                </td>
                                <td>
                                    <div><i class="far fa-calendar-alt me-1"></i> <?php echo date('d M Y', strtotime($b['booking_date'])); ?></div>
                                    <div><i class="far fa-clock me-1"></i> <?php echo date('h:i A', strtotime($b['booking_time'])); ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo $b['service_name']; ?></div>
                                    <small class="text-muted"><?php echo $b['duration']; ?> minutes</small>
                                </td>
                                <td>
                                    <span class="fw-bold">RM <?php echo number_format($b['price'], 2); ?></span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $b['status']; ?>">
                                        <?php echo ucfirst($b['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
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
                                        <a href="?delete=<?php echo $b['id']; ?>&status=<?php echo $status_filter; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                                           class="btn-icon btn-delete" 
                                           onclick="return confirm('Are you sure you want to delete this booking?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>