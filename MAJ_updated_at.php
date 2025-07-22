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
        SELECT oh.id_order, oh.date_add
        FROM ps_order_history oh
        INNER JOIN (
        SELECT id_order, MIN(date_add) AS latest_date
            FROM ps_order_history
            GROUP BY id_order
        ) latest ON latest.id_order = oh.id_order AND latest.latest_date = oh.date_add
        JOIN ps_order_state os ON os.id_order_state = oh.id_order_state
        WHERE os.paid = 1
    ) last_paid ON last_paid.id_order = o.id_order
    SET o.validated_at = last_paid.date_add;");

    $stmt->execute();
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}
