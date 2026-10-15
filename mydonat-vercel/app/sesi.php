<?php
// Session disimpan di database (serverless tidak punya penyimpanan session bersama)
require_once __DIR__ . '/config.php';

class DbSessionHandler implements SessionHandlerInterface {
    public function open(string $path, string $name): bool {
        mysqli_query(koneksi_db(), "CREATE TABLE IF NOT EXISTS php_sessions (
            id VARCHAR(128) NOT NULL PRIMARY KEY,
            data MEDIUMBLOB NOT NULL,
            updated_at INT NOT NULL
        ) ENGINE=InnoDB");
        return true;
    }
    public function close(): bool { return true; }
    public function read(string $id): string|false {
        $stmt = mysqli_prepare(koneksi_db(), "SELECT data FROM php_sessions WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 's', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $data);
        $has = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        return $has ? (string)$data : '';
    }
    public function write(string $id, string $data): bool {
        $now = time();
        $stmt = mysqli_prepare(koneksi_db(), "REPLACE INTO php_sessions (id, data, updated_at) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssi', $id, $data, $now);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }
    public function destroy(string $id): bool {
        $stmt = mysqli_prepare(koneksi_db(), "DELETE FROM php_sessions WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 's', $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }
    public function gc(int $max): int|false {
        $old = time() - $max;
        mysqli_query(koneksi_db(), "DELETE FROM php_sessions WHERE updated_at < " . (int)$old);
        return 0;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_save_handler(new DbSessionHandler(), true);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.gc_maxlifetime', '86400');
    session_start();
}
