<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS leave_validation (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        employee_name VARCHAR(255) NOT NULL,
        leave_date DATE NOT NULL,
        shift VARCHAR(100) NOT NULL,
        validation_status ENUM('Pending', 'Validated', 'Rejected') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql);
    echo "Table 'leave_validation' created successfully or already exists.\n";
    
} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage() . "\n");
}
?>
