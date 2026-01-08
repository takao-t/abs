<?php
session_start();
$error_message = '';

require_once 'php/config.php';
require_once 'php/AbspManager.php';

// --- AMI接続インスタンス化 ---
$ami = new \AbspFunctions\AbspManager(AMI_HOST, AMI_USER, AMI_PASS, AMI_PORT);

//ユーザの存在有無チェック
function user_exists($ami) { // 引数に $ami を渡す形に変更
    $users = $ami->getFamilyDB('ABS/PANELUSER');
    if(empty($users)){
        return false;
    } else {
        return true;
    }
}

if (user_exists($ami)) {
    // ユーザーが一人でもいる場合にはログインページへ
    header('Location: login.php');
    exit;
}

// フォームが送信された場合
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']) ?? '';
    $password = trim($_POST['password']) ?? '';
    $password_confirm = trim($_POST['password_confirm']) ?? '';

    // バリデーション
    if (empty($username) || empty($password)) {
        $error_message = 'ユーザー名とパスワードを入力してください。';
    } elseif ($password !== $password_confirm) {
        $error_message = 'パスワードが一致しません。';
    } else {
        // パスワードハッシュを生成
	$hashed_pass = password_hash($password, PASSWORD_DEFAULT);
        // AstDBにユーザ名とハッシュを登録
        $ami->putDbItem('ABS/PANELUSER', $username, $hashed_pass);

        // 保存されたハッシュが取得できなければAstDBへの保存が失敗しているのでエラー
        $stored_hash = $ami->getDbItem('ABS/PANELUSER', $username); 
        
        if($stored_hash == $hashed_pass){
            $result = true;
        } else {
            $result = false;
        }

        if ($result) {
            // 登録成功したならログインページへ
            $_SESSION['is_logged_in'] = true;
            $_SESSION['username'] = $username;
            header('Location: login.php');
            exit;
        } else {
           $error_message = 'ユーザー登録に失敗しました。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>新規ユーザー登録 - ABSコントロールパネル</title>
    <link rel="stylesheet" href="style.min.css">
</head>
<body>
    <div class="login-container">
        <h2>最初のユーザーを登録</h2>
        <p>システムにユーザーが存在しません。最初の管理者ユーザーを登録してください。</p>
        <?php if ($error_message): ?>
            <p class="error"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form action="register.php" method="POST">
            <div>
                <label for="username">ユーザー名:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div>
                <label for="password">パスワード:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div>
                <label for="password_confirm">パスワード (確認用):</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            <button type="submit" class="btn">登録</button>
        </form>
    </div>
</body>
</html>
