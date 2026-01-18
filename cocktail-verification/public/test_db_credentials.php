<?php
// test_db_credentials.php

$host = 'srv545.hstgr.io';
$dbname = 'u974801020_tagTest';
$username = 'u974801020_test_tag';
$password = 'error404@PHP';
$port = 3306;

echo "Testing database connection...<br>";
echo "Host: $host<br>";
echo "Database: $dbname<br>";
echo "Username: $username<br>";
echo "Port: $port<br><br>";

try {
    // Test without PDO::ATTR_PERSISTENT first
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 10,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "<span style='color: green; font-weight: bold;'>✓ Connection successful!</span><br><br>";
    
    // Test a query
    $stmt = $pdo->query("SELECT DATABASE() as db");
    $result = $stmt->fetch();
    echo "Connected to database: " . $result['db'] . "<br>";
    
    // Show tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . count($tables) . "<br>";
    if (!empty($tables)) {
        echo implode(', ', $tables);
    }
    
} catch (PDOException $e) {
    echo "<span style='color: red; font-weight: bold;'>✗ Connection failed:</span><br>";
    echo "Error Code: " . $e->getCode() . "<br>";
    echo "Error Message: " . $e->getMessage() . "<br><br>";
    
    // Common error codes:
    switch ($e->getCode()) {
        case 1044:
            echo "Error: Access denied for user to database.<br>";
            echo "Make sure the user has permission to access the database.";
            break;
        case 1045:
            echo "Error: Access denied - invalid username or password.<br>";
            echo "Check your username and password.";
            break;
        case 2002:
        case 2003:
            echo "Error: Cannot connect to MySQL server.<br>";
            echo "Check if the host is correct and MySQL is running.";
            break;
        case 1049:
            echo "Error: Database does not exist.<br>";
            echo "Check if the database name is correct.";
            break;
        default:
            echo "Unknown database error.";
    }
}
?>