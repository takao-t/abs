document.addEventListener('DOMContentLoaded', function() {
    // すべての親メニュー項目を取得
    const menuParents = document.querySelectorAll('.menu-parent > a');

    menuParents.forEach(function(parent) {
        parent.addEventListener('click', function(event) {
            // リンクのデフォルト動作（ページ遷移）をキャンセル
            event.preventDefault();

            // クリックされた親メニューのli要素とその下のul（サブメニュー）を取得
            const parentLi = this.parentElement;
            const submenu = parentLi.querySelector('.submenu');

            // activeクラスとopenクラスを付け外しする
            parentLi.classList.toggle('active');
            submenu.classList.toggle('open');
        });
    });

    const helpTitles = document.querySelectorAll('.help-title');

    helpTitles.forEach(function(title) {
        title.addEventListener('click', function() {
            // クリックされたタイトルの次の要素（説明文）を取得
            const content = this.nextElementSibling;

            // activeクラスとopenクラスを付け外し
            this.classList.toggle('active');
            content.classList.toggle('open');
        });
    });

    // メニュー左へ折りたたみ機能
    const menuToggleButton = document.getElementById('menu-toggle-btn');
    const body = document.body;

    if (menuToggleButton) {
        menuToggleButton.addEventListener('click', function() {
            body.classList.toggle('menu-collapsed');

            // 現在の状態をlocalStorageに保存する (任意)
            if (body.classList.contains('menu-collapsed')) {
                localStorage.setItem('menuState', 'collapsed');
            } else {
                localStorage.setItem('menuState', 'expanded');
            }
        });

        // ページ読み込み時にlocalStorageの状態を復元する (任意)
        const savedMenuState = localStorage.getItem('menuState');
        if (savedMenuState === 'collapsed') {
            body.classList.add('menu-collapsed');
        }
    }

});

document.addEventListener('DOMContentLoaded', () => {
    // 必要なHTML要素をページに挿入
    const modalHtml = `
        <div id="help-modal-backdrop" class="modal-backdrop"></div>
        <div id="help-modal-content" class="modal-content" style="display: none;">
            <div class="modal-header">
                <h3 id="help-modal-title">ヘルプ</h3>
                <button id="help-modal-close-btn" class="modal-close-btn">&times;</button>
            </div>
            <div id="help-modal-body" class="modal-body">
                <p>ヘルプを読み込んでいます...</p>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // ページタイトル (h2) の横にヘルプアイコンを設置
    const pageTitleElement = document.querySelector('.content h2');
    if (pageTitleElement) {
        const helpIcon = document.createElement('span');
        helpIcon.id = 'help-icon';
        helpIcon.className = 'help-icon';
        helpIcon.textContent = '?';
        helpIcon.title = 'このページのヘルプを表示';
        
        pageTitleElement.style.display = 'flex';
        pageTitleElement.style.alignItems = 'center';
        pageTitleElement.appendChild(helpIcon);

        // イベントリスナーを設定
        const backdrop = document.getElementById('help-modal-backdrop');
        const modal = document.getElementById('help-modal-content');
        const modalTitle = document.getElementById('help-modal-title');
        const modalBody = document.getElementById('help-modal-body');
        const closeBtn = document.getElementById('help-modal-close-btn');

        // ヘルプアイコンがクリックされたときの処理
        helpIcon.addEventListener('click', async () => {
            // URLから現在のページIDを取得 (例: pbx-detail-page)
            const params = new URLSearchParams(window.location.search);
            const currentPageId = params.get('page') || 'top';
            const helpFilePath = `help/${currentPageId}.html`;

            // モーダルタイトルを更新
            modalTitle.textContent = `${pageTitleElement.childNodes[0].textContent.trim()} のヘルプ`;
            
            // ヘルプファイルの内容を非同期で取得
            try {
                const response = await fetch(helpFilePath);
                if (!response.ok) {
                    //throw new Error('ヘルプファイルが見つかりません。');
                    throw new Error('この項目のヘルプはありません。');
                }
                const helpHtml = await response.text();
                modalBody.innerHTML = helpHtml;
            } catch (error) {
                modalBody.innerHTML = `<p style="color: #ffb000;">${error.message}</p>`;
            }
            
            // モーダルを表示
            backdrop.style.display = 'block';
            modal.style.display = 'flex';
        });
        
        // モーダルを閉じる関数
        const closeModal = () => {
            backdrop.style.display = 'none';
            modal.style.display = 'none';
            modalBody.innerHTML = '<p>ヘルプを読み込んでいます...</p>'; // 内容をリセット
        };

        // 閉じるボタン、背景クリックでモーダルを閉じる
        closeBtn.addEventListener('click', closeModal);
        backdrop.addEventListener('click', closeModal);
        
        // Escapeキーでモーダルを閉じる
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display !== 'none') {
                closeModal();
            }
        });
    }
});
