<?php

$servername = "87.98.141.171";
$port = 3306;
$username = "districlos_refonte";
$password = "TXM1qjk9teu_cfb9wxv";
$db = 'districlos_refonte';
$dsn = "mysql:host=$servername;port=$port;dbname=$db;charset=utf8";
try {
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("UPDATE ps_orders o
    JOIN (
        SELECT oh.id_order, MIN(oh.date_add) AS validated_date
        FROM ps_order_history oh
        JOIN ps_order_state os ON os.id_order_state = oh.id_order_state
        WHERE os.paid = 1
        GROUP BY oh.id_order
    ) AS first_paid ON first_paid.id_order = o.id_order
    SET o.validated_at = first_paid.validated_date
    WHERE o.validated_at = '0000-00-00 00:00:00';");

    $stmt->execute();
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}
