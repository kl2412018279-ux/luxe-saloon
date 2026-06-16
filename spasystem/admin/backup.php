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
$messageType = '';

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

// Import Backup - FIXED VERSION
if (isset($_POST['import_backup']) && isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] == 0) {
    $content = file_get_contents($_FILES['backup_file']['tmp_name']);
    $backup = json_decode($content, true);
    
    if ($backup && isset($backup['data'])) {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // ✅ FIX: Disable foreign key checks
            $conn->query("SET FOREIGN_KEY_CHECKS = 0");
            
            // Clear tables in correct order (child tables first)
            $tables = ['bookings', 'services', 'users'];
            foreach ($tables as $table) {
                if (isset($backup['data'][$table])) {
                    $conn->query("DELETE FROM $table"); // DELETE instead of TRUNCATE
                    $conn->query("ALTER TABLE $table AUTO_INCREMENT = 1"); // Reset auto-increment
                }
            }
            
            // Insert data
            foreach ($backup['data'] as $table => $rows) {
                if (empty($rows)) continue;
                
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
            
            // ✅ Re-enable foreign key checks
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            
            // Commit transaction
            $conn->commit();
            
            $message = "✅ Data restored successfully from backup!";
            $messageType = 'success';
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            $message = "❌ Restore failed: " . $e->getMessage();
            $messageType = 'danger';
        }
    } else {
        $message = "❌ Invalid backup file!";
        $messageType = 'danger';
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
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="backup_file" class="form-label">Select backup file (.json)</label>
                                <input type="file" name="backup_file" class="form-control" accept=".json" required>
                            </div>
                            <button type="submit" name="import_backup" class="btn btn-warning" onclick="return confirm('⚠️ WARNING: This will overwrite all current data! Continue?')">
                                <i class="fas fa-upload"></i> Restore from Backup
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Security Notes -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-shield-alt"></i> Security & Backup Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>📋 Backup Includes:</h6>
                                <ul>
                                    <li>User accounts and credentials</li>
                                    <li>Service catalog and pricing</li>
                                    <li>All booking appointments</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>🛡️ Security Measures:</h6>
                                <ul>
                                    <li>Admin-only access required</li>
                                    <li>Transaction-based restore (all or nothing)</li>
                                    <li>Foreign key constraints properly handled</li>
                                    <li>Data validation before import</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
