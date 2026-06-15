<?php
// Simple XSS protection
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

// Handle Add Service
if (isset($_POST['add_service'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = (float)$_POST['price'];
    $duration = (int)$_POST['duration'];
    $image_icon = mysqli_real_escape_string($conn, $_POST['image_icon']);
    $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO services (name, description, price, duration, image_icon, image_url, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdissi", $name, $description, $price, $duration, $image_icon, $image_url, $is_active);
    
    if ($stmt->execute()) {
        $success = "Service added successfully!";
    } else {
        $error = "Error adding service: " . $conn->error;
    }
}

// Handle Edit Service
if (isset($_POST['edit_service'])) {
    $id = (int)$_POST['service_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = (float)$_POST['price'];
    $duration = (int)$_POST['duration'];
    $image_icon = mysqli_real_escape_string($conn, $_POST['image_icon']);
    $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE services SET name=?, description=?, price=?, duration=?, image_icon=?, image_url=?, is_active=? WHERE id=?");
    $stmt->bind_param("ssdissii", $name, $description, $price, $duration, $image_icon, $image_url, $is_active, $id);
    
    if ($stmt->execute()) {
        $success = "Service updated successfully!";
    } else {
        $error = "Error updating service: " . $conn->error;
    }
}

// Handle Delete Service
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Check if service has bookings
    $check = $conn->query("SELECT COUNT(*) FROM bookings WHERE service_id = $id")->fetch_row()[0];
    
    if ($check > 0) {
        $error = "Cannot delete this service because it has existing bookings. Archive it instead.";
    } else {
        $conn->query("DELETE FROM services WHERE id = $id");
        $success = "Service deleted successfully!";
    }
}

// Handle Toggle Status (Active/Inactive)
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $current = $conn->query("SELECT is_active FROM services WHERE id = $id")->fetch_assoc();
    $new_status = $current['is_active'] ? 0 : 1;
    $conn->query("UPDATE services SET is_active = $new_status WHERE id = $id");
    $success = "Service status updated!";
}

// Get all services
$services = $conn->query("SELECT * FROM services ORDER BY id");

// Get service for editing
$edit_service = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit_service = $conn->query("SELECT * FROM services WHERE id = $id")->fetch_assoc();
}

// Get statistics
$total_services = $conn->query("SELECT COUNT(*) FROM services")->fetch_row()[0];
$active_services = $conn->query("SELECT COUNT(*) FROM services WHERE is_active = 1")->fetch_row()[0];
$inactive_services = $conn->query("SELECT COUNT(*) FROM services WHERE is_active = 0")->fetch_row()[0];
$most_booked = $conn->query("
    SELECT s.name, COUNT(b.id) as booking_count 
    FROM services s 
    LEFT JOIN bookings b ON s.id = b.service_id 
    GROUP BY s.id 
    ORDER BY booking_count DESC 
    LIMIT 1
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxe Salon | Manage Services</title>
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
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(102,126,234,0.1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .stat-icon i {
            font-size: 24px;
            color: #667eea;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: #1a1a2e;
        }

        .stat-label {
            font-size: 13px;
            color: #666;
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 24px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .form-card h4 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #1a1a2e;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }

        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }

        .service-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .service-image {
            height: 160px;
            overflow: hidden;
            position: relative;
        }

        .service-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .service-card:hover .service-image img {
            transform: scale(1.05);
        }

        .service-status {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-active {
            background: #10b981;
            color: white;
        }

        .status-inactive {
            background: #ef4444;
            color: white;
        }

        .service-body {
            padding: 20px;
        }

        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .service-name {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .service-price {
            font-size: 20px;
            font-weight: 800;
            color: #667eea;
        }

        .service-description {
            color: #666;
            font-size: 13px;
            margin: 10px 0;
            line-height: 1.5;
        }

        .service-meta {
            display: flex;
            gap: 15px;
            margin: 15px 0;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        .service-meta span {
            font-size: 12px;
            color: #888;
        }

        .service-meta i {
            margin-right: 5px;
            color: #667eea;
        }

        .service-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-icon {
            flex: 1;
            padding: 8px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
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

        .btn-toggle {
            background: #fef3c7;
            color: #d97706;
        }

        .btn-toggle:hover {
            background: #d97706;
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

        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 24px;
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
            .services-grid { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
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
            <a href="bookings.php">
                <i class="fas fa-calendar-alt"></i> <span>Bookings</span>
            </a>
            <a href="services.php" class="active">
                <i class="fas fa-cut"></i> <span>Services</span>
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
                <h2>Manage Services</h2>
                <p>Add, edit, and manage your salon services</p>
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
                <div class="stat-icon"><i class="fas fa-cut"></i></div>
                <div class="stat-value"><?php echo $total_services; ?></div>
                <div class="stat-label">Total Services</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?php echo $active_services; ?></div>
                <div class="stat-label">Active Services</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-ban"></i></div>
                <div class="stat-value"><?php echo $inactive_services; ?></div>
                <div class="stat-label">Inactive Services</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-value"><?php echo $most_booked ? $most_booked['name'] : '-'; ?></div>
                <div class="stat-label">Most Booked</div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Add/Edit Service Form -->
        <div class="form-card">
            <h4><i class="fas fa-<?php echo $edit_service ? 'edit' : 'plus'; ?> me-2"></i> <?php echo $edit_service ? 'Edit Service' : 'Add New Service'; ?></h4>
            <form method="POST" action="">
                <?php if($edit_service): ?>
                    <input type="hidden" name="service_id" value="<?php echo $edit_service['id']; ?>">
                <?php endif; ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Service Name *</label>
                        <input type="text" name="name" class="form-control" value="<?php echo $edit_service ? htmlspecialchars($edit_service['name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Price (RM) *</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $edit_service ? $edit_service['price'] : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Duration (minutes) *</label>
                        <input type="number" name="duration" class="form-control" value="<?php echo $edit_service ? $edit_service['duration'] : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Icon Class (Font Awesome)</label>
                        <input type="text" name="image_icon" class="form-control" placeholder="fa-cut, fa-spa, etc." value="<?php echo $edit_service ? $edit_service['image_icon'] : 'fa-cut'; ?>">
                        <small class="text-muted">e.g., fa-cut, fa-spa, fa-hand-peace</small>
                    </div>
                    <div class="form-group">
                        <label>Image URL</label>
                        <input type="text" name="image_url" class="form-control" placeholder="https://images.unsplash.com/..." value="<?php echo $edit_service ? htmlspecialchars($edit_service['image_url']) : ''; ?>">
                        <small class="text-muted">Optional - leave empty for default icon</small>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="is_active" class="form-select">
                            <option value="1" <?php echo ($edit_service && $edit_service['is_active']) ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo ($edit_service && !$edit_service['is_active']) ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Describe this service..."><?php echo $edit_service ? htmlspecialchars($edit_service['description']) : ''; ?></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" name="<?php echo $edit_service ? 'edit_service' : 'add_service'; ?>" class="btn btn-primary px-4">
                        <i class="fas fa-<?php echo $edit_service ? 'save' : 'plus'; ?> me-1"></i> <?php echo $edit_service ? 'Update Service' : 'Add Service'; ?>
                    </button>
                    <?php if($edit_service): ?>
                        <a href="services.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Services List -->
        <h4 class="mb-3"><i class="fas fa-list me-2"></i> All Services</h4>
        
        <?php if($services->num_rows == 0): ?>
            <div class="empty-state">
                <i class="fas fa-cut"></i>
                <h5>No Services Yet</h5>
                <p>Add your first service using the form above.</p>
            </div>
        <?php else: ?>
            <div class="services-grid">
                <?php while($service = $services->fetch_assoc()): ?>
                    <div class="service-card">
                        <div class="service-image">
                            <?php if($service['image_url']): ?>
                                <img src="<?php echo $service['image_url']; ?>" alt="<?php echo $service['name']; ?>">
                            <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1560066984-138dadb4c035?w=400&h=160&fit=crop" alt="<?php echo $service['name']; ?>">
                            <?php endif; ?>
                            <div class="service-status <?php echo $service['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $service['is_active'] ? 'Active' : 'Inactive'; ?>
                            </div>
                        </div>
                        <div class="service-body">
                            <div class="service-header">
                                <h5 class="service-name"><?php echo $service['name']; ?></h5>
                                <div class="service-price">RM <?php echo number_format($service['price'], 2); ?></div>
                            </div>
                            <p class="service-description"><?php echo $service['description'] ?: 'No description available.'; ?></p>
                            <div class="service-meta">
                                <span><i class="fas fa-clock"></i> <?php echo $service['duration']; ?> min</span>
                                <span><i class="fas fa-tag"></i> <?php echo $service['image_icon'] ?: 'fa-cut'; ?></span>
                            </div>
                            <div class="service-actions">
                                <a href="?edit=<?php echo $service['id']; ?>" class="btn-icon btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="?toggle=<?php echo $service['id']; ?>" class="btn-icon btn-toggle" onclick="return confirm('Toggle service status?')">
                                    <i class="fas fa-<?php echo $service['is_active'] ? 'eye-slash' : 'eye'; ?>"></i> <?php echo $service['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </a>
                                <a href="?delete=<?php echo $service['id']; ?>" class="btn-icon btn-delete" onclick="return confirm('Delete this service? This cannot be undone if there are no bookings.')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>