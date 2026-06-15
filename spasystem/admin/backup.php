<?php
// Simple XSS protection
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$message = '';

// Export Backup
if (isset($_POST['export_backup'])) {
    $tables = ['users', 'services', 'bookings'];
    $data = [];
    
    foreach ($tables as $table) {
        $result = $conn->query("SELECT * FROM $table");
        $data[$table] = [];
        while ($row = $result->fetch_assoc()) {
            $data[$table][] = $row;
        }
    }
    
    $backup = [
        'timestamp' => date('Y-m-d H:i:s'),
        'salon_name' => 'Luxe Saloon',
        'data' => $data
    ];
    
    $json = json_encode($backup, JSON_PRETTY_PRINT);
    $filename = 'luxe_salon_backup_' . date('Y-m-d') . '.json';
    
    header('Content-Type: application/json');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    echo $json;
    exit();
}

// Import Backup
if (isset($_POST['import_backup']) && isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] == 0) {
    $content = file_get_contents($_FILES['backup_file']['tmp_name']);
    $backup = json_decode($content, true);
    
    if ($backup && isset($backup['data'])) {
        foreach ($backup['data'] as $table => $rows) {
            $conn->query("TRUNCATE TABLE $table");
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $placeholders = array_fill(0, count($columns), '?');
                $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
                $stmt = $conn->prepare($sql);
                $types = str_repeat('s', count($columns));
                $stmt->bind_param($types, ...array_values($row));
                $stmt->execute();
            }
        }
        $message = "Data restored successfully from backup!";
    } else {
        $message = "Invalid backup file!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Recovery - Luxe Saloon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-spa"></i> Luxe Saloon Admin</a>
            <a href="dashboard.php" class="btn btn-sm btn-outline-light">Back to Dashboard</a>
        </div>
    </nav>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-download"></i> Export Backup</h5>
                    </div>
                    <div class="card-body">
                        <p>Download complete system backup including all users, services and bookings.</p>
                        <form method="POST">
                            <button type="submit" name="export_backup" class="btn btn-success">
                                <i class="fas fa-download"></i> Download Backup (JSON)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0"><i class="fas fa-upload"></i> Disaster Recovery</h5>
                    </div>
                    <div class="card-body">
                        <p>Restore system from a previous backup file.</p>
                        <?php if($message): ?>
                            <div class="alert alert-info"><?php echo $message; ?></div>
                        <?php endif; ?>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="file" name="backup_file" class="form-control mb-3" accept=".json" required>
                            <button type="submit" name="import_backup" class="btn btn-warning" onclick="return confirm('WARNING: This will overwrite all current data! Continue?')">
                                <i class="fas fa-upload"></i> Restore from Backup
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>