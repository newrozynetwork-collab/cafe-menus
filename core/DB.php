<?php
/**
 * Database wrapper — PDO SQLite singleton
 */
class DB {
    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            $path = defined('ROOT') ? ROOT : dirname(__DIR__);
            $dsn  = 'sqlite:' . $path . '/database/tirana.db';
            self::$instance = new PDO($dsn);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$instance->exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL;');
        }
        return self::$instance;
    }

    /** Run a query and return all rows */
    public static function all(string $sql, array $params = []): array {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Run a query and return one row */
    public static function one(string $sql, array $params = []): ?array {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Run a query and return a single value */
    public static function val(string $sql, array $params = []): mixed {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /** Execute an INSERT/UPDATE/DELETE, return lastInsertId or rowCount */
    public static function run(string $sql, array $params = []): int {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return (int) self::get()->lastInsertId() ?: $stmt->rowCount();
    }

    /** Insert a row and return its new ID */
    public static function insert(string $table, array $data): int {
        $cols = implode(', ', array_keys($data));
        $phs  = implode(', ', array_fill(0, count($data), '?'));
        $stmt = self::get()->prepare("INSERT INTO $table ($cols) VALUES ($phs)");
        $stmt->execute(array_values($data));
        return (int) self::get()->lastInsertId();
    }

    /** Update rows matching a where clause */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $set  = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
        $stmt = self::get()->prepare("UPDATE $table SET $set WHERE $where");
        $stmt->execute([...array_values($data), ...$whereParams]);
        return $stmt->rowCount();
    }

    /** Delete rows */
    public static function delete(string $table, string $where, array $params = []): int {
        $stmt = self::get()->prepare("DELETE FROM $table WHERE $where");
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function begin():    void { self::get()->beginTransaction(); }
    public static function commit():   void { self::get()->commit(); }
    public static function rollback(): void { self::get()->rollBack(); }
}
