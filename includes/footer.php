        </main>

        <?php if (!empty($includeSiteFooter ?? false)): ?>

        <footer class="site-footer" id="contact">

            <div class="footer-contact">

                <h2 class="footer-contact-title">Contact us</h2>

                <ul class="footer-contact-list">

                    <li>

                        <span class="footer-label">Phone</span>

                        <a href="tel:<?= h(CONTACT_PHONE_TEL) ?>"><?= h(CONTACT_PHONE) ?></a>

                    </li>

                    <li>

                        <span class="footer-label">Email</span>

                        <a href="mailto:<?= h(CONTACT_EMAIL) ?>"><?= h(CONTACT_EMAIL) ?></a>

                    </li>

                    <li>

                        <span class="footer-label">Social</span>

                        <div class="footer-social-row">

                            <a href="<?= h(CONTACT_INSTAGRAM_URL) ?>" target="_blank" rel="noopener noreferrer">Instagram</a>

                            <span class="footer-social-sep" aria-hidden="true">·</span>

                            <a href="<?= h(CONTACT_FACEBOOK_URL) ?>" target="_blank" rel="noopener noreferrer">Facebook</a>

                        </div>

                    </li>

                </ul>

            </div>

            <p class="footer-copy">&copy; <?= (int) date('Y') ?> <?= h(SITE_NAME) ?>. All rights reserved.<br> Made By Danish</p>

        </footer>

        <?php endif; ?>

    </div>

</div>

<?php if (function_exists('current_user') && current_user() !== null): ?>

<script>

(function () {

    var toggle = document.getElementById('sidebar-toggle');

    var sidebar = document.getElementById('app-sidebar');

    if (!toggle || !sidebar) return;



    function syncToggleUi(collapsed) {

        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

        toggle.setAttribute('aria-label', collapsed ? 'Show menu' : 'Hide menu');

        toggle.classList.toggle('is-sidebar-collapsed', collapsed);

    }



    toggle.addEventListener('click', function () {

        var collapsed = !sidebar.classList.contains('is-collapsed');

        sidebar.classList.toggle('is-collapsed', collapsed);

        syncToggleUi(collapsed);

    });

})();

</script>

<?php endif; ?>

</body>

</html>

