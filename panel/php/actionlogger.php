<?php

/**
 * ユーザーの操作ログをSQLite3データベースに記録するクラス
 * ファイル配置
 * WebUIのroot/php -> このPHP
 * WebUIのroot/db  -> 記録用DB
 */
class ActionLogger
{
    // 記録用DB 
    private const DB_PATH = __DIR__ . '/../db/activity.sqlite3';

    //コンストラクタ
    public function __construct()
    {
        // 定義されたパスのディレクトリ部分を取得
        $dir = dirname(self::DB_PATH);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new \Exception("ログディレクトリの作成に失敗しました: {$dir}");
            }
        }
    }

    /**
     * ログをデータベースに記録
     *
     * @param string $who    操作を行ったユーザー名など
     * @param string $what   操作内容
     * @return bool          成功した場合はtrue、失敗した場合はfalse
     */
    public function log(string $who, string $what): bool
    {
        try {
            $db = new SQLite3(self::DB_PATH);
            $db->busyTimeout(5000); 

            //ローカルタイムの場合(タイムゾーンはここで指定)
            $cdate = new DateTime(null, new DateTimeZone('Asia/Tokyo'));
            $timestamp = $cdate->format('Y-m-d H:i:s');
            //スーパーグローバルからIPアドレスと現在のページを取得
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            $currentPage = $_SERVER['REQUEST_URI'] ?? 'UNKNOWN';

            $stmt = $db->prepare(
                'INSERT INTO activity_log (timestamp, username, page, action, ip_address) 
                 VALUES (:timestamp, :username, :page,  :action, :ip_address)'
            );

            if (!$stmt) {
                $this->initializeDatabase();
                 $stmt = $db->prepare(
                    'INSERT INTO activity_log (timestamp, username, page, action, ip_address) 
                     VALUES (:timestamp, :username, :page, :action, :ip_address)'
                );
            }
            
            $stmt->bindValue(':timestamp', $timestamp, SQLITE3_TEXT);
            $stmt->bindValue(':username', $who, SQLITE3_TEXT);
            $stmt->bindValue(':page', $currentPage, SQLITE3_TEXT);
            $stmt->bindValue(':action', $what, SQLITE3_TEXT);
            $stmt->bindValue(':ip_address', $ipAddress, SQLITE3_TEXT);

            $result = $stmt->execute();
            
            $stmt->close();
            $db->close();

            return $result !== false;

        } catch (\Exception $e) {
            error_log("ActionLogger Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * データベースとテーブルを初期化（存在しない場合のみ作成）
     */
    public function initializeDatabase(): void
    {
        try {
            $db = new SQLite3(self::DB_PATH);
            $db->busyTimeout(5000);
            
            $db->exec(
                'CREATE TABLE IF NOT EXISTS activity_log (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    timestamp TEXT NOT NULL,
                    username TEXT NOT NULL,
                    page TEXT NOT NULL,
                    action TEXT NOT NULL,
                    ip_address TEXT
                )'
            );
            
            $db->close();
        } catch (\Exception $e) {
            error_log("ActionLogger DB Initialization Error: " . $e->getMessage());
        }
    }
}
