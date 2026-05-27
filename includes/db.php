<?php
/**
 * Database Connection (PDO Singleton)
 * Anjuman E Ezzy - Live Streaming Platform
 */

require_once __DIR__ . '/../config/config.php';

class Database {
    private static ?Database $instance = null;
    private PDO $pdo;
    private string $driver;

    private function __construct() {
        $this->driver = strtolower(DB_DRIVER);
        if (in_array($this->driver, ['pgsql', 'postgres', 'postgresql'], true)) {
            $dsn = 'pgsql:host=' . DB_HOST;
            if (DB_PORT !== '') {
                $dsn .= ';port=' . DB_PORT;
            }
            $dsn .= ';dbname=' . DB_NAME . ';options=--client_encoding=UTF8;sslmode=' . DB_SSLMODE;
        } else {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        }
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB Connection failed: ' . $e->getMessage());
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
        }
    }

    private function __clone() {}

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPDO(): PDO { return $this->pdo; }

    public function driverName(): string {
        return $this->driver;
    }

    public function isPostgres(): bool {
        return in_array($this->driver, ['pgsql', 'postgres', 'postgresql'], true);
    }

    public function columnExists(string $table, string $column, ?string $schema = null): bool {
        $schema = $schema ?: DB_SCHEMA;

        try {
            $result = $this->fetchOne(
                'SELECT 1 AS ok FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
                [$schema, $table, $column]
            );
            return (bool) $result;
        } catch (Exception $e) {
            return false;
        }
    }

    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchOne(string $sql, array $params = []): ?array {
        return $this->query($sql, $params)->fetch() ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert(string $sql, array $params = []): string {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    public function execute(string $sql, array $params = []): int {
        return $this->query($sql, $params)->rowCount();
    }
}
