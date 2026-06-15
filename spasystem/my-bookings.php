<?php
require_once 'config/database.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

$bookings = $conn->query("
    SELECT b.*, s.name as service_name, s.price, s.duration 
    FROM bookings b 
    JOIN services s ON b.service_id = s.id 
    WHERE b.user_id = $user_id 
    ORDER BY b.booking_date DESC, b.booking_time DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Luxe Saloon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-spa"></i> Luxe Saloon</a>
            <div>
                <span class="text-white me-3"><?php echo $_SESSION['user_name']; ?></span>
                <a href="dashboard.php" class="btn btn-sm btn-outline-light">Back</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> All My Bookings</h5>
            </div>
            <div class="card-body">
                <?php if($bookings->num_rows == 0): ?>
                    <div class="text-center py-5">No bookings found</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Booking No</th>
                                    <th>Service</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($b = $bookings->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $b['booking_no']; ?></td>
                                    <td><?php echo $b['service_name']; ?></td>
                                    <td><?php echo date('d M Y', strtotime($b['booking_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($b['booking_time'])); ?></td>
                                    <td>RM <?php echo $b['price']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $b['status'] == 'confirmed' ? 'success' : ($b['status'] == 'pending' ? 'warning' : 'secondary'); ?>">
                                            <?php echo ucfirst($b['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>