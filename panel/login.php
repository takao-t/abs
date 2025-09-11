<?php
ob_start();
session_start();
define('ABS_PANEL_INCLUDED', true);

// --- 設定値 ---
const MAX_LOGIN_ATTEMPTS = 3; // 最大試行回数
const LOCKOUT_TIME_SECONDS = 300; // ロック時間(秒)

require_once 'php/config.php';
require_once 'php/astman.php';
require_once 'php/functions.php';
require_once 'php/abscache.php';

$mycache = new absCache();

$error_message = '';
$reason = $_GET['reason'] ?? '';

//ユーザリストを取得(配列リターン)
$reg_users = AbspFunctions\get_db_family('ABS/PANELUSER');
if(empty($reg_users)){
    // ユーザリストが取得できない場合Asteriskが実行中かを確認
    $ast_version = AbspFunctions\exec_cli_command('core show version');
    if($ast_version === false){
        $error_message = 'Asteriskが起動していないためログインできまません。システムをチェックしてください。';
    }
    else {
        // ユーザーが一人もいない場合には登録ページへ
        header('Location: register.php');
        exit;
    }
}


if ($reason === 'session_expired') {
    $error_message = 'セッションがタイムアウトしました。再度ログインしてください。';
} elseif ($reason === 'concurrent_login') {
    $error_message = 'このユーザは既に他の場所でログインしています。';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. ロック状態を確認
    if (isset($_SESSION['lockout_until']) && $_SESSION['lockout_until'] > time()) {
        $remaining_time = $_SESSION['lockout_until'] - time();
        $error_message = "ログイン試行回数が上限に達しました。あと{$remaining_time}秒お待ちください。";
    
    } else {
        // ロックが解除された場合は、関連セッション変数をクリア
        if (isset($_SESSION['lockout_until'])) {
            unset($_SESSION['login_attempts']);
            unset($_SESSION['lockout_until']);
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        //Asteriskが実行中かを確認 
        $ast_version = AbspFunctions\exec_cli_command('core show version');
        if($ast_version !== false){
            if (empty($username) || empty($password)) {
                $error_message = 'ユーザ名とパスワードを入力してください。';
            } else {
                $stored_hash = AbspFunctions\get_db_item('ABS/PANELUSER', $username);
            
                if ($stored_hash !== '' && password_verify($password, $stored_hash)) {
                    // --- ログイン成功時の処理 ---
                    
                    // 失敗カウントをリセット
                    unset($_SESSION['login_attempts']);
                    unset($_SESSION['lockout_until']);
                    
                    // --- 同時ログイン制御 (先勝ち) ---
                    $existing_session_id = $mycache->get("ABS/PANELUSER/{$username}", 'session_id');
                    $last_activity_timestamp = $mycache->get("ABS/PANELUSER/{$username}", 'last_activity');
                
                    $session_timeout_minutes = AbspFunctions\get_db_item('ABS/PANEL', 'SESSION_TIMEOUT') ?? 30;
                    $session_lifetime_seconds = (int)$session_timeout_minutes * 60;
                
                    $is_session_active = false;
                    if (!empty($existing_session_id) && !empty($last_activity_timestamp)) {
                        if ((time() - (int)$last_activity_timestamp) <= $session_lifetime_seconds) {
                            $is_session_active = true;
                        }
                    }

                    if ($is_session_active) {
                        $error_message = 'このユーザは既に他の場所でログインしています。';
                    } else {
                        session_regenerate_id(true); 
                        $new_session_id = session_id();
                        $current_time = time();

                        // ログイン情報をキャッシュに保存
                        $mycache->set("ABS/PANELUSER/{$username}", 'session_id', $new_session_id);
                        $mycache->set("ABS/PANELUSER/{$username}", 'last_activity', $current_time);

                        $_SESSION['is_logged_in'] = true;
                        $_SESSION['username'] = $username;
                        $_SESSION['last_activity'] = $current_time;

                        header('Location: index.php');
                        exit;
                    }
                } else {
                    // --- ログイン失敗時の処理 ---
                    if (!isset($_SESSION['login_attempts'])) {
                        $_SESSION['login_attempts'] = 0;
                    }
                    $_SESSION['login_attempts']++;

                    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
                        // アカウントをロック
                        $_SESSION['lockout_until'] = time() + LOCKOUT_TIME_SECONDS;
                        $error_message = "ログイン試行回数が上限に達しました。しばらくしてから再度お試しください。";
                    } else {
                        $remaining_attempts = MAX_LOGIN_ATTEMPTS - $_SESSION['login_attempts'];
                        $error_message = "ユーザ名またはパスワードが正しくありません。(残りの試行回数: {$remaining_attempts}回)";
                    }
                }
            }
        } else {
            $error_message = 'Asteriskが起動していないためログインできまません。システムをチェックしてください。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ABSコントロールパネル - ログイン</title>
    <link rel="stylesheet" href="style.min.css">
    <style>
        /* --- ログインページ専用ヘルプモーダル位置調整 --- */
        #help-modal.help-modal-overlay {
            position: fixed; /* 画面に固定 */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: none;
            justify-content: center;
            align-items: center;
            padding: 1rem;
            z-index: 1000;
            box-sizing: border-box;
        }
        #help-modal .help-modal-content {
            background-color: var(--surface-color);
            margin: 0;
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <h2 style="position: relative;">
            ABSコントロールパネル
            <span id="login-help-icon" class="help-icon" style="position: absolute; right: 0; top: 5px;">?</span>
        </h2>
        
        <?php if ($error_message): ?>
            <div class="error"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div>
                <label for="username">ユーザ名</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div>
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">ログイン</button>
        </form>
    </div>

    <div id="help-modal" class="help-modal-overlay" style="display: none;">
        <div class="help-modal-content">
            <span class="help-modal-close">&times;</span>
            <div id="help-modal-body">
                </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const helpIcon = document.getElementById('login-help-icon');
    const modal = document.getElementById('help-modal');
    const modalBody = document.getElementById('help-modal-body');
    const closeModal = document.querySelector('.help-modal-close');

    if (!helpIcon || !modal || !closeModal) return;

    // ヘルプアイコンクリック時の処理
    helpIcon.addEventListener('click', () => {
        fetch('help/login.html')
            .then(response => {
                if (!response.ok) {
                    throw new Error('ヘルプファイルの読み込みに失敗しました。');
                }
                return response.text();
            })
            .then(html => {
                modalBody.innerHTML = html;
                modal.style.display = 'flex';
            })
            .catch(error => {
                modalBody.innerHTML = `<p style="color: #f44336;">${error.message}</p>`;
                modal.style.display = 'flex';
            });
    });

    // 閉じるボタンクリック時の処理
    closeModal.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    // モーダルの外側クリック時の処理
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});
</script>

</body>
</html>
