document.addEventListener('DOMContentLoaded', function () {
    const toggleButton = document.getElementById('theme-toggle-btn');
    const body = document.body;

    // --- 1. ページ読み込み時に、記憶したテーマを適用する ---
    // localStorageから 'theme' の値を取得
    const savedTheme = localStorage.getItem('theme');
    
    // もし 'light' が保存されていたら、ライトモードを適用
    if (savedTheme === 'light') {
        body.classList.add('light-mode');
    }

    // --- 2. ボタンクリック時の処理 ---
    toggleButton.addEventListener('click', function () {
        // bodyのクラスを切り替え
        body.classList.toggle('light-mode');

        // --- 3. 現在のテーマをlocalStorageに保存する ---
        if (body.classList.contains('light-mode')) {
            // ライトモードなら 'light' を保存
            localStorage.setItem('theme', 'light');
        } else {
            // ダークモードなら 'dark' を保存
            localStorage.setItem('theme', 'dark');
        }
    });
})
