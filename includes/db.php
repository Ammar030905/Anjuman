<?php
/**
 * Database Connection (PDO Singleton)
 * Anjuman E Ezzy - Live Streaming Platform
 */

require_once __DIR__ . '/../config/config.php';

class Database {
    private static ?Database $instance = null;
    private static array $columnExistsCache = [];
    private PDO $pdo;
    private string $driver;
    private string $host;
    private string $name;
    private string $port;
    private string $user;
    private string $pass;
    private string $sslmode;

    private function __construct() {
        $this->driver = strtolower(DB_DRIVER);
        $this->host = DB_HOST;
        $this->name = DB_NAME;
        $this->port = DB_PORT;
        $this->user = DB_USER;
        $this->pass = DB_PASS;
        $this->sslmode = DB_SSLMODE;

        if (DATABASE_URL !== '') {
            $parsed = parse_url(DATABASE_URL);
            if ($parsed !== false) {
                $scheme = strtolower($parsed['scheme'] ?? '');
                if (in_array($scheme, ['postgres', 'postgresql', 'pgsql'], true)) {
                    $this->driver = 'pgsql';
                }

                if (!empty($parsed['host'])) {
                    $this->host = $parsed['host'];
                }

                if (!empty($parsed['port'])) {
                    $this->port = (string) $parsed['port'];
                }

                if (!empty($parsed['path'])) {
                    $this->name = ltrim($parsed['path'], '/');
                }

                if (!empty($parsed['user'])) {
                    $this->user = rawurldecode($parsed['user']);
                }

                if (array_key_exists('pass', $parsed)) {
                    $this->pass = rawurldecode((string) $parsed['pass']);
                }

                if (!empty($parsed['query'])) {
                    parse_str($parsed['query'], $query);
                    if (!empty($query['sslmode'])) {
                        $this->sslmode = (string) $query['sslmode'];
                    }
                }
            }
        }

        if (str_contains($this->host, '://')) {
            $parsedHost = parse_url($this->host, PHP_URL_HOST);
            if (is_string($parsedHost) && $parsedHost !== '') {
                $this->host = $parsedHost;
            }
        }

        if (in_array($this->driver, ['pgsql', 'postgres', 'postgresql'], true)) {
            $dsn = 'pgsql:host=' . $this->host;
            if ($this->port !== '') {
                $dsn .= ';port=' . $this->port;
            }
            $dsn .= ';dbname=' . $this->name . ';options=--client_encoding=UTF8;sslmode=' . $this->sslmode . ';connect_timeout=5;application_name=AnjumanEEzzy';
        } else {
            $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->name . ';charset=' . DB_CHARSET;
        }
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => APP_ENV !== 'development',
        ];
        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            error_log('DB Connection failed: driver=' . $this->driver . ', host=' . $this->host . ', db=' . $this->name . ', error=' . $e->getMessage());
            http_response_code(500);
            $message = APP_ENV === 'development'
                ? 'Database connection failed: ' . $e->getMessage()
                : 'Database connection failed.';
            die(json_encode(['success' => false, 'message' => $message]));
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
        $cacheKey = strtolower($schema . '.' . $table . '.' . $column . '.' . $this->driver);

        if (array_key_exists($cacheKey, self::$columnExistsCache)) {
            return self::$columnExistsCache[$cacheKey];
        }

        try {
            $result = $this->fetchOne(
                'SELECT 1 AS ok FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
                [$schema, $table, $column]
            );
            self::$columnExistsCache[$cacheKey] = (bool) $result;
            return self::$columnExistsCache[$cacheKey];
        } catch (Exception $e) {
            self::$columnExistsCache[$cacheKey] = false;
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
