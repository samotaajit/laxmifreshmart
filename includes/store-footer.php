</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a href="<?= BASE_URL ?>index.php" class="footer-brand">Laxmi Fresh Mart</a>
            <p>Fresh vegetables, fruits and dairy products delivered to our approved service areas.</p>
        </div>
        <div>
            <h4>Shop</h4>
            <a href="<?= BASE_URL ?>category.php?slug=vegetables">Vegetables</a>
            <a href="<?= BASE_URL ?>category.php?slug=fruits">Fruits</a>
            <a href="<?= BASE_URL ?>category.php?slug=dairy-products">Dairy Products</a>
        </div>
        <div>
            <h4>Account</h4>
            <a href="<?= BASE_URL ?>login.php">Login</a>
            <a href="<?= BASE_URL ?>signup.php">Create Account</a>
            <a href="<?= BASE_URL ?>cart.php">Cart</a>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© <?= date('Y') ?> Laxmi Fresh Mart. All rights reserved.</span>
        <span>Fresh • Local • Trusted</span>
    </div>
</footer>
<script src="<?= BASE_URL ?>assets/js/store.js"></script>
</body>
</html>
