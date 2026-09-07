<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Dashboard';
?>

<header class="admin-header">

    <div class="header-left">

        <button
            type="button"
            class="sidebar-toggle"
            id="sidebarToggle"
            aria-label="Open menu"
        >
            ☰
        </button>

        <div>

            <h1 class="header-title">
                <?= e($pageTitle) ?>
            </h1>

            <div class="header-breadcrumb">
                Laxmi Fresh Mart / <?= e($pageTitle) ?>
            </div>

        </div>

    </div>


    <div class="header-right">

        <div class="admin-profile">

            <div class="admin-avatar">
                <?= e(strtoupper(substr(adminName(), 0, 1))) ?>
            </div>

            <div class="admin-profile-info">

                <strong>
                    <?= e(adminName()) ?>
                </strong>

                <small>
                    Administrator
                </small>

            </div>

        </div>

    </div>

</header>