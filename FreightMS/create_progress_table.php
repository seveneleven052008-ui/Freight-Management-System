<?php
require_once 'c:/xampp/htdocs/FMS/config/config.php';
$pdo = getDBConnection();

try {
    $sql = "CREATE TABLE IF NOT EXISTS skill_development_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        assessment_id INT NOT NULL,
        competency_name VARCHAR(255) NOT NULL,
        previous_level VARCHAR(50),
        new_level VARCHAR(50),
        progress_type ENUM('Improvement', 'Persistent Gap', 'New Competency') DEFAULT 'Improvement',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES users(id),
        FOREIGN KEY (assessment_id) REFERENCES skill_assessments(id)
    )";
    $pdo->exec($sql);
    echo "Table 'skill_development_progress' created successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
