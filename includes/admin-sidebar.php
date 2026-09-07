<?php

declare(strict_types=1);

$currentPage = $currentPage ?? '';
?>

<aside class="admin-sidebar" id="adminSidebar">

    <div class="sidebar-brand">

        <a href="<?= ADMIN_URL ?>dashboard.php">

            <img
                src="<?= BASE_URL ?>assets/images/logo.png"
                alt="Laxmi Fresh Mart"
            >

            <div class="brand-text">
                <strong>Laxmi Fresh Mart</strong>
                <small>Admin Panel</small>
            </div>

        </a>

    </div>


    <nav class="sidebar-nav">

        <div class="sidebar-section">
            MAIN
        </div>

        <a
            href="<?= ADMIN_URL ?>dashboard.php"
            class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>"
        >
            <span class="sidebar-icon">▦</span>
            <span>Dashboard</span>
        </a>


        <div class="sidebar-section">
            CUSTOMERS
        </div>

        <a
            href="<?= ADMIN_URL ?>requests/"
            class="sidebar-link <?= $currentPage === 'requests' ? 'active' : '' ?>"
        >
            <span class="sidebar-icon">♙</span>
            <span>User Requests</span>
        </a>

        <a
            href="<?= ADMIN_URL ?>users/"
            class="sidebar-link <?= $currentPage === 'users' ? 'active' : '' ?>"
        >
            <span class="sidebar-icon">♟</span>
            <span>Users</span>
        </a>


        <div class="sidebar-section">
            CATALOG
        </div>

        <a
            href="<?= ADMIN_URL ?>categories/"
            class="sidebar-link <?= $currentPage === 'categories' ? 'active' : '' ?>"
        >
            <span class="sidebar-icon">▤</span>
            <span>Categories</span>
        </a>

        <a
            href="<?= ADMIN_URL ?>units/"
            class="sidebar-link <?= $currentPage === 'units' ? 'active' : '' ?>"
        >
            <span class="sidebar-icon">◫</span>
            <span>Units</span>
        </a>

        <a
            href="<?= ADMIN_URL ?>products/"
            class="sidebar-link <?= $currentPage === 'products' ? 'active' : '' ?>"
        >
            <span class="sidebar-icon">▣</span>
            <span>Products</span>
        </a>

        <a
            href="<?= ADMIN_URL ?>inventory/"
            class="sidebar-link <?= $currentPage === 'inventory' ? 'active' : '' ?>"
        >
            <span class="sidebar-icon">◈</span>
            <span>Inventory</span>
        </a>


        <div class="sidebar-section">
            SALES
        </div>

        <a
            href="<?= ADMIN_URL ?>orders/"
            class="sidebar-link <?= $currentPage === 'orders' ? 'active' : '' ?>"
        >
            <span class="sidebar-icon">▣</span>
            <span>Orders</span>
        </a>


        <div class="sidebar-section">
            ACCOUNT
        </div>

        <a
            href="<?= ADMIN_URL ?>logout.php"
            class="sidebar-link logout-link"
        >
            <span class="sidebar-icon">↪</span>
            <span>Logout</span>
        </a>

    </nav>

</aside>

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>