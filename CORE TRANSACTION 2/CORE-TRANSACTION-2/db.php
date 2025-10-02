<?php
// Simple PDO wrapper and transaction logger

$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('DB_NAME') ?: 'hotel_core';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_CHARSET = 'utf8mb4';
$DB_PORT = getenv('DB_PORT') ?: '3307';

function ensure_database_exists(): void {
  global $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $DB_PASS, $DB_CHARSET;
  $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};charset={$DB_CHARSET}";
  $opt = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ];
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $opt);
  $dbNameQuoted = str_replace('`', '``', $DB_NAME);
  $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbNameQuoted}` CHARACTER SET {$DB_CHARSET} COLLATE utf8mb4_unicode_ci");
}

function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;
  global $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $DB_PASS, $DB_CHARSET;
  ensure_database_exists();
  $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset={$DB_CHARSET}";
  $opt = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ];
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $opt);
  return $pdo;
}

function init_schema(): void {
  $sql1 = "CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tx_date DATETIME NOT NULL,
            module VARCHAR(64) NOT NULL,
            reference VARCHAR(128) NOT NULL,
            amount DECIMAL(12,2) NULL,
            status VARCHAR(64) NOT NULL
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  db()->exec($sql1);

  $sql2 = "CREATE TABLE IF NOT EXISTS reservations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reservation_no VARCHAR(32) NOT NULL UNIQUE,
            guest VARCHAR(128) NOT NULL,
            room VARCHAR(64) NOT NULL,
            check_in DATE NOT NULL,
            check_out DATE NOT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'Booked',
            source VARCHAR(32) NOT NULL DEFAULT 'Online',
            remarks TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  db()->exec($sql2);

  $sql3 = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(128) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('admin','staff','customer') NOT NULL DEFAULT 'customer',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  db()->exec($sql3);

  $sql4 = "CREATE TABLE IF NOT EXISTS activities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ts DATETIME NOT NULL,
            module VARCHAR(64) NOT NULL,
            action VARCHAR(64) NOT NULL,
            reference VARCHAR(128) NULL,
            user_id INT NULL,
            meta JSON NULL
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  db()->exec($sql4);

  $sql5 = "CREATE TABLE IF NOT EXISTS rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_no VARCHAR(32) NOT NULL UNIQUE,
            type VARCHAR(64) NOT NULL DEFAULT 'Standard',
            status ENUM('Available','Occupied','Cleaning','Maintenance','Blocked') NOT NULL DEFAULT 'Available',
            rate DECIMAL(12,2) NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  db()->exec($sql5);

  // Seed demo rooms if empty
  try {
    $cnt = (int)db()->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
    if ($cnt === 0) {
      $stmt = db()->prepare("INSERT INTO rooms (room_no, type, status, rate) VALUES (?,?,?,?)");
      $demo = [
        ['101','Standard','Available',1500],
        ['102','Standard','Occupied',1500],
        ['103','Deluxe','Available',2200],
        ['104','Deluxe','Cleaning',2200],
        ['201','Suite','Available',3500],
        ['202','Suite','Maintenance',3500]
      ];
      foreach ($demo as $r) { $stmt->execute($r); }
    }
  } catch (Throwable $e) { /* ignore if table not accessible yet */ }
}

function log_transaction(string $module, string $reference, ?float $amount, string $status): void {
  init_schema();
  $stmt = db()->prepare("INSERT INTO transactions (tx_date, module, reference, amount, status) VALUES (NOW(), ?, ?, ?, ?)");
  $stmt->execute([$module, $reference, $amount, $status]);
}

function log_activity(string $module, string $action, ?string $reference = null, ?int $userId = null, $meta = null): void {
  init_schema();
  $metaJson = $meta !== null ? json_encode($meta) : null;
  $stmt = db()->prepare("INSERT INTO activities (ts, module, action, reference, user_id, meta) VALUES (NOW(), ?, ?, ?, ?, ?)");
  $stmt->execute([$module, $action, $reference, $userId, $metaJson]);
}

function fetch_transactions(int $limit = 50, ?int $sinceId = null): array {
  init_schema();
  if ($sinceId) {
    $stmt = db()->prepare("SELECT id, tx_date, module, reference, amount, status FROM transactions WHERE id > ? ORDER BY id ASC LIMIT ?");
    $stmt->bindValue(1, $sinceId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
  } else {
    $stmt = db()->prepare("SELECT id, tx_date, module, reference, amount, status FROM transactions ORDER BY id DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
  }
  $stmt->execute();
  return $stmt->fetchAll();
}

// Reservation helpers
function reservation_generate_no(): string {
  return 'RF-' . random_int(1000, 9999);
}

function reservation_create(string $guest, string $room, string $checkIn, string $checkOut, string $source = 'Online', ?string $remarks = null): array {
  init_schema();
  $reservationNo = reservation_generate_no();
  $stmt = db()->prepare("INSERT INTO reservations (reservation_no, guest, room, check_in, check_out, status, source, remarks) VALUES (?, ?, ?, ?, ?, 'Booked', ?, ?)");
  $stmt->execute([$reservationNo, $guest, $room, $checkIn, $checkOut, $source, $remarks]);
  return reservation_get_by_no($reservationNo);
}

function reservation_get_by_no(string $reservationNo): array {
  init_schema();
  $stmt = db()->prepare("SELECT * FROM reservations WHERE reservation_no = ?");
  $stmt->execute([$reservationNo]);
  $row = $stmt->fetch();
  return $row ?: [];
}

function reservation_get_by_id(int $id): array {
  init_schema();
  $stmt = db()->prepare("SELECT * FROM reservations WHERE id = ?");
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  return $row ?: [];
}

function reservation_list(int $limit = 100, int $offset = 0): array {
  init_schema();
  $stmt = db()->prepare("SELECT * FROM reservations ORDER BY id DESC LIMIT ? OFFSET ?");
  $stmt->bindValue(1, $limit, PDO::PARAM_INT);
  $stmt->bindValue(2, $offset, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll();
}

function reservation_update(string $reservationNo, array $fields): array {
  init_schema();
  if (!$fields) return reservation_get_by_no($reservationNo);
  $allowed = ['guest','room','check_in','check_out','status','source','remarks'];
  $sets = [];
  $vals = [];
  foreach ($fields as $k => $v) {
    if (in_array($k, $allowed, true)) { $sets[] = "$k = ?"; $vals[] = $v; }
  }
  if (!$sets) return reservation_get_by_no($reservationNo);
  $vals[] = $reservationNo;
  $sql = "UPDATE reservations SET ".implode(', ',$sets)." WHERE reservation_no = ?";
  $stmt = db()->prepare($sql);
  $stmt->execute($vals);
  return reservation_get_by_no($reservationNo);
}

function reservation_delete(string $reservationNo): bool {
  init_schema();
  $stmt = db()->prepare("DELETE FROM reservations WHERE reservation_no = ?");
  return $stmt->execute([$reservationNo]);
}

// User helpers
function user_create(string $name, string $email, string $password, string $role = 'customer'): array {
  init_schema();
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = db()->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
  $stmt->execute([$name, strtolower($email), $hash, $role]);
  $id = (int)db()->lastInsertId();
  return user_get_by_id($id);
}

function user_get_by_email(string $email): array {
  init_schema();
  $stmt = db()->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
  $stmt->execute([strtolower($email)]);
  $row = $stmt->fetch();
  return $row ?: [];
}

function user_get_by_id(int $id): array {
  init_schema();
  $stmt = db()->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  return $row ?: [];
}

function user_verify_password(array $userRow, string $password): bool {
  if (!$userRow) return false;
  return password_verify($password, $userRow['password_hash'] ?? '');
}
?>


