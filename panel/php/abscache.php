<?php
//キャッシュ・ドライバ(現在のサポート形式はfileかmemcached)
//現在はWebUIのセッションキャッシュとして使用
class absCache {

    private static $cache_dir;
    private static $memcached_instance = null;
    private static $active_mode = null;

    public function __construct() {
        if (self::$active_mode !== null) {
            return;
        }

        $preferred_mode = defined('CACHE_MODE') ? CACHE_MODE : 'file';

        if ($preferred_mode === 'memcached') {
            if (class_exists('Memcached')) {
                self::$memcached_instance = new Memcached();
                // addServerは接続を試みず、サーバーリストに追加するだけなので、ここではエラーにならない
                self::$memcached_instance->addServer(MEMCACHED_HOST, MEMCACHED_PORT);
                
                // getStats()で実際に接続できるかテストする
                if (@self::$memcached_instance->getStats() === false) {
                    error_log('absCache Warning: Could not connect to Memcached server. Falling back to file cache.');
                    self::$memcached_instance = null;
                    self::$active_mode = 'file';
                } else {
                    self::$active_mode = 'memcached';
                }
            } else {
                error_log('absCache Notice: Memcached extension is not installed. Falling back to file cache.');
                self::$active_mode = 'file';
            }
        } else {
            self::$active_mode = 'file';
        }
        
        if (self::$active_mode === 'file') {
            self::$cache_dir = __DIR__ . '/../cache';
            if (!is_dir(self::$cache_dir)) {
                @mkdir(self::$cache_dir, 0755, true);
            }
        }
    }

    private static function substName(string $family, string $key): string {
        return str_replace('/', '_', ltrim($family, '/')) . '-' . $key;
    }

    public function set(string $family, string $key, $value, int $expiration = 0): bool {
        $cache_key = self::substName($family, $key);
        if (empty($cache_key) || $value === null) {
            return false;
        }

        if (self::$active_mode === 'memcached' && self::$memcached_instance) {
            return self::$memcached_instance->set($cache_key, $value, $expiration);
        } else {
            $file_path = self::$cache_dir . '/' . $cache_key;
            return file_put_contents($file_path, serialize($value)) !== false;
        }
    }

    public function get(string $family, string $key) {
        $cache_key = self::substName($family, $key);
        if (empty($cache_key)) {
            return null;
        }

        if (self::$active_mode === 'memcached' && self::$memcached_instance) {
            $value = self::$memcached_instance->get($cache_key);
            return ($value === false) ? null : $value;
        } else {
            $file_path = self::$cache_dir . '/' . $cache_key;
            if (!file_exists($file_path)) {
                return null;
            }
            $content = @file_get_contents($file_path);
            return ($content === false) ? null : unserialize($content);
        }
    }

    public function del(string $family, string $key): bool {
        $cache_key = self::substName($family, $key);
        if (empty($cache_key)) {
            return false;
        }

        if (self::$active_mode === 'memcached' && self::$memcached_instance) {
            return self::$memcached_instance->delete($cache_key);
        } else {
            $file_path = self::$cache_dir . '/' . $cache_key;
            if (file_exists($file_path)) {
                return unlink($file_path);
            }
            return true;
        }
    }
}
?>
