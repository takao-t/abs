<?php
if (!defined('ABS_PANEL_INCLUDED')) {
    die("Direct access is not permitted.");
}

require_once 'menu-structure.php'; // メニューの構造を定義したファイルを読み込む
?>
<ul>
    <?php foreach ($menuStructure as $section): ?>

        <?php // --- トップメニュー項目の処理 --- ?>
        <?php if (isset($section['type']) && $section['type'] === 'top'): ?>
            <li>
                <a href="index.php?page=<?= $section['page'] ?>" class="menu-top <?= ($currentPage == $section['page']) ? 'active' : '' ?>">
                    <?= htmlspecialchars($section['text'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            </li>

        <?php // --- 区切り線の処理 --- ?>
        <?php elseif (isset($section['type']) && $section['type'] === 'divider'): ?>
            <li class="menu-section-start"></li>

        <?php // --- 折りたたみサブメニューの処理 --- ?>
        <?php elseif (isset($section['type']) && $section['type'] === 'submenu'): ?>
            <?php
            // このサブメニューに現在アクティブなページが含まれているか確認
            $isActiveParent = in_array($currentPage, array_column($section['items'], 'page'));
            ?>
            <li class="menu-parent <?= $isActiveParent ? 'active' : '' ?>">
                <a href="#"><?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?></a>
                <ul class="submenu <?= $isActiveParent ? 'open' : '' ?>">
                    <?php foreach ($section['items'] as $item): ?>
                        <li>
                            <a href="index.php?page=<?= $item['page'] ?>" class="<?= ($currentPage == $item['page']) ? 'active' : '' ?>">
                                <?= htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>

        <?php // --- 通常のメニュー項目の処理 --- ?>
        <?php elseif (isset($section['section_items'])): ?>
            <?php foreach ($section['section_items'] as $item): ?>
                <li>
                    <a href="index.php?page=<?= $item['page'] ?>" class="<?= ($currentPage == $item['page']) ? 'active' : '' ?>">
                        <?= htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php endforeach; ?>
    
    <?php // --- ログアウトとテーマ切替 (ループ外の固定項目) --- ?>
    <li class="menu-section-start">
        <a href="logout.php">
            ログアウト (<?= htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
        </a>
    </li>
    <li class="menu-section-start">
        <div style="padding: 8px;">
            <button id="theme-toggle-btn" class="btn" style="width: 100%;">テーマ切替</button>
        </div>
    </li>
</ul>
