<?php
require_once 'config/database.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

$bookings = $conn->query("
    SELECT b.*, s.name as service_name, s.price, s.duration, s.image_icon, s.image_url
    FROM bookings b 
    JOIN services s ON b.service_id = s.id 
    WHERE b.user_id = $user_id 
    ORDER BY b.booking_date DESC, b.booking_time DESC
    LIMIT 5
");

$services = $conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY id");

$booking_success = '';
$booking_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_service'])) {
    $service_id = (int)$_POST['service_id'];
    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $booking_no = generateBookingNo();
    
    $stmt = $conn->prepare("INSERT INTO bookings (booking_no, user_id, service_id, customer_name, customer_email, customer_phone, booking_date, booking_time, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siissssss", $booking_no, $user_id, $service_id, $user['full_name'], $user['email'], $user['phone'], $booking_date, $booking_time, $notes);
    
    if ($stmt->execute()) {
        $booking_success = "Booking confirmed! Your booking number is <strong>$booking_no</strong>";
    } else {
        $booking_error = "Something went wrong. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxe Saloon | My Dashboard</title>
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
            background: #f8fafc;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 20px rgba(0,0,0,0.05);
            padding: 15px 0;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-section {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 30px;
            margin: 20px 0 30px;
            padding: 40px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -20%;
            width: 250px;
            height: 250px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }

        .service-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            height: 100%;
        }

        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
        }

        .service-image {
            height: 200px;
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
            transform: scale(1.1);
        }

        .service-overlay {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0,0,0,0.6);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .service-body {
            padding: 20px;
            text-align: center;
        }

        .service-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: -35px auto 15px;
            position: relative;
            z-index: 2;
            box-shadow: 0 5px 15px rgba(102,126,234,0.3);
        }

        .service-icon i {
            font-size: 22px;
            color: white;
        }

        .service-price {
            font-size: 22px;
            font-weight: 800;
            color: #667eea;
            margin: 10px 0 5px;
        }

        .service-duration {
            font-size: 13px;
            color: #888;
        }

        .booking-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
            border-left: 4px solid;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }

        .booking-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .booking-confirmed { border-left-color: #10b981; }
        .booking-pending { border-left-color: #f59e0b; }
        .booking-completed { border-left-color: #3b82f6; }
        .booking-cancelled { border-left-color: #ef4444; }

        .btn-book {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
        }

        footer {
            background: #1a1a2e;
            color: white;
            padding: 40px 0;
            margin-top: 60px;
        }

        @media (max-width: 768px) {
            .hero-section { padding: 25px; text-align: center; }
            .service-card { margin-bottom: 20px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-spa me-2"></i>Luxe Saloon
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted">Hi, <?php echo $_SESSION['user_name']; ?></span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold">Welcome back, <?php echo $_SESSION['user_name']; ?>! ✨</h1>
                    <p class="lead mt-2 opacity-90">Book your next beauty appointment in seconds</p>
                    <button class="btn btn-light rounded-pill px-4 py-2 mt-3 fw-bold" data-bs-toggle="modal" data-bs-target="#bookModal">
                        <i class="fas fa-calendar-plus me-2"></i>Book New Appointment
                    </button>
                </div>
                <div class="col-md-4 text-center">
                    <i class="fas fa-spa fa-4x opacity-50"></i>
                </div>
            </div>
        </div>

        <?php if($booking_success): ?>
            <div class="alert alert-success"><?php echo $booking_success; ?></div>
        <?php endif; ?>
        <?php if($booking_error): ?>
            <div class="alert alert-danger"><?php echo $booking_error; ?></div>
        <?php endif; ?>

        <!-- Services Section with Images -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold"><i class="fas fa-cut me-2" style="color: #667eea;"></i> Our Premium Services</h3>
            <span class="text-muted"><?php echo $services->num_rows; ?> services available</span>
        </div>
        <div class="row mb-5">
            <?php while($service = $services->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="service-card" onclick="openBookingModal(<?php echo $service['id']; ?>, '<?php echo addslashes($service['name']); ?>', <?php echo $service['price']; ?>)">
                    <div class="service-image">
                        <?php if($service['image_url']): ?>
                            <img src="<?php echo $service['image_url']; ?>" alt="<?php echo $service['name']; ?>">
                        <?php else: ?>
                            <img src="https://images.unsplash.com/photo-1560066984-138dadb4c035?w=400&h=300&fit=crop" alt="<?php echo $service['name']; ?>">
                        <?php endif; ?>
                        <div class="service-overlay">
                            <i class="fas fa-clock"></i> <?php echo $service['duration']; ?> min
                        </div>
                    </div>
                    <div class="service-body">
                        <div class="service-icon">
                            <i class="fas <?php echo $service['image_icon']; ?>"></i>
                        </div>
                        <h5 class="fw-bold mb-2"><?php echo $service['name']; ?></h5>
                        <p class="text-muted small"><?php echo $service['description']; ?></p>
                        <div class="service-price">RM <?php echo $service['price']; ?></div>
                        <button class="btn btn-sm btn-outline-primary rounded-pill mt-2">Book Now →</button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Recent Bookings -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold"><i class="fas fa-history me-2" style="color: #667eea;"></i> Recent Bookings</h3>
            <a href="my-bookings.php" class="text-decoration-none">View All <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
        
        <?php if($bookings->num_rows == 0): ?>
            <div class="text-center py-5 bg-white rounded-4">
                <i class="fas fa-calendar-times fa-4x text-muted opacity-25"></i>
                <h5 class="mt-3">No bookings yet</h5>
                <p class="text-muted">Book your first appointment to get started</p>
                <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#bookModal">Book Now</button>
            </div>
        <?php else: ?>
            <?php while($b = $bookings->fetch_assoc()): ?>
            <div class="booking-card booking-<?php echo $b['status']; ?>">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <div class="fw-bold"><?php echo $b['service_name']; ?></div>
                        <small class="text-muted"><?php echo $b['booking_no']; ?></small>
                    </div>
                    <div class="col-md-3">
                        <i class="far fa-calendar-alt me-1 text-muted"></i> <?php echo date('d M Y', strtotime($b['booking_date'])); ?>
                        <br><i class="far fa-clock me-1 text-muted"></i> <?php echo date('h:i A', strtotime($b['booking_time'])); ?>
                    </div>
                    <div class="col-md-2">
                        <span class="fw-bold">RM <?php echo $b['price']; ?></span>
                    </div>
                    <div class="col-md-2">
                        <span class="badge bg-<?php echo $b['status'] == 'confirmed' ? 'success' : ($b['status'] == 'pending' ? 'warning' : ($b['status'] == 'completed' ? 'info' : 'secondary')); ?> px-3 py-2">
                            <?php echo ucfirst($b['status']); ?>
                        </span>
                    </div>
                    <div class="col-md-2 text-md-end">
                        <button class="btn btn-sm btn-outline-secondary" onclick="alert('Contact us: +60 12 345 6789\nEmail: hello@luxesalon.com')">
                            <i class="fas fa-phone"></i> Contact
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                    <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Book Appointment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="service_id" id="modal_service_id">
                        
                        <div class="text-center mb-4" id="selectedServiceDisplay">
                            <i class="fas fa-cut fa-3x text-primary opacity-50"></i>
                            <h5 id="selectedServiceName" class="mt-2">Select a service</h5>
                            <p class="text-primary fw-bold" id="selectedServicePrice"></p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Date</label>
                            <input type="date" name="booking_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Time</label>
                            <select name="booking_time" class="form-control" required>
                                <option value="">Choose time slot</option>
                                <option value="09:00:00">09:00 AM</option>
                                <option value="10:00:00">10:00 AM</option>
                                <option value="11:00:00">11:00 AM</option>
                                <option value="12:00:00">12:00 PM</option>
                                <option value="13:00:00">01:00 PM</option>
                                <option value="14:00:00">02:00 PM</option>
                                <option value="15:00:00">03:00 PM</option>
                                <option value="16:00:00">04:00 PM</option>
                                <option value="17:00:00">05:00 PM</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Special Requests</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Any special requests or notes?"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="book_service" class="btn btn-primary rounded-pill px-4">Confirm Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer>
        <div class="container text-center">
            <p>&copy; 2024 Luxe Saloon. All rights reserved.</p>
            <small class="opacity-75">123 Beauty Street, Kuala Lumpur | +60 12 345 6789 | hello@luxesalon.com</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openBookingModal(serviceId, serviceName, servicePrice) {
            document.getElementById('modal_service_id').value = serviceId;
            document.getElementById('selectedServiceName').innerHTML = serviceName;
            document.getElementById('selectedServicePrice').innerHTML = 'RM ' + servicePrice;
            
            var myModal = new bootstrap.Modal(document.getElementById('bookModal'));
            myModal.show();
        }
    </script>
</body>
</html>