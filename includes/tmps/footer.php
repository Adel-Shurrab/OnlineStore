<div class="footer_body">
    <footer class="section-footer border-top">
        <div class="container">
            <section class="footer-top py-4">
                <div class="row">
                    <aside class="col-md-4">
                        <article>
                            <img src="layout/images/fa_footer.png" class="logo-footer">
                            <p class="mt-3 description"><?php echo lang($langArray['footer_description']); ?></p>
                            <div class="d-flex gap-2 another-options">
                                <a class="btn btn-icon btn-light facebook" title="Facebook" target="_blank" href="#" data-abc="true"><i class="fa fa-facebook"></i></a>
                                <a class="btn btn-icon btn-light instagram" title="Instagram" target="_blank" href="#" data-abc="true"><img src="layout/images/instagram.png" alt=""></a>
                                <a class="btn btn-icon btn-light youtube" title="Youtube" target="_blank" href="#" data-abc="true"><i class="fa fa-youtube-play" aria-hidden="true"></i></a>
                                <a class="btn btn-icon btn-light twitter" title="Twitter" target="_blank" href="#" data-abc="true"><i class="fa fa-twitter"></i></a>
                            </div>
                        </article>
                    </aside>
                    <aside class="col-sm-3 col-md-2">
                        <h6 class="title"><?php echo lang($langArray['about_us']); ?></h6>
                        <ul class="list-unstyled">
                            <li><a href="#" data-abc="true"><?php echo lang($langArray['about_us']); ?></a></li>
                            <li><a href="#" data-abc="true"><?php echo lang($langArray['services']); ?></a></li>
                            <li><a href="#" data-abc="true"><?php echo lang($langArray['terms_condition']); ?></a></li>
                            <li><a href="#" data-abc="true"><?php echo lang($langArray['our_blogs']); ?></a></li>
                        </ul>
                    </aside>
                    <aside class="col-sm-3 col-md-2">
                        <h6 class="title"><?php echo lang($langArray['services']); ?></h6>
                        <ul class="list-unstyled">
                            <li><a href="#" data-abc="true"><?php echo lang($langArray['help_center']); ?></a></li>
                            <li><a href="#" data-abc="true"><?php echo lang($langArray['money_refund']); ?></a></li>
                            <li><a href="#" data-abc="true"><?php echo lang($langArray['terms_policy']); ?></a></li>
                            <li><a href="#" data-abc="true"><?php echo lang($langArray['open_dispute']); ?></a></li>
                        </ul>
                    </aside>
                    <aside class="col-sm-3 col-md-2">
                        <h6 class="title"><?php echo lang($langArray['for_users']); ?></h6>
                        <ul class="list-unstyled">
                            <li><a href="logout.php" data-abc="true"><?php echo lang($langArray['user_login']); ?></a></li>
                            <li><a href="logout.php" data-abc="true"><?php echo lang($langArray['user_register']); ?></a></li>
                            <li><a href="profile-settings.php" data-abc="true"><?php echo lang($langArray['account_setting']); ?></a></li>
                            <li><a href="cart.php" data-abc="true"><?php echo lang($langArray['my_cart']); ?></a></li>
                        </ul>
                    </aside>
                    <aside class="col-sm-2 col-md-2">
                        <h6 class="title"><?php echo lang($langArray['our_app']); ?></h6>
                        <a href="#" class="d-block mb-2" data-abc="true">
                            <img class="" src="layout/images/googleApp.png" height="40">
                        </a>
                        <a href="#" class="d-block mb-2" data-abc="true">
                            <img class="" src="layout/images/appstore.png" height="40" width="123">
                        </a>
                    </aside>
                </div>
            </section>
            <section class="footer-copyright border-top">
                <div class="copyright_cont">
                    <p class="text-muted"><?php echo lang($langArray['copyright_text']); ?></p>
                    <p class="text-muted">
                        <a href="#" data-abc="true"><?php echo lang($langArray['privacy_cookies']); ?></a> &nbsp; &nbsp;
                        <a href="#" data-abc="true"><?php echo lang($langArray['accessibility']); ?></a>
                    </p>
                </div>
            </section>
        </div>
    </footer>
</div>

<script src="<?php echo $js ?>jquery-3.4.1.min.js"></script>
<script src="<?php echo $js ?>bootstrap.min.js"></script>
<script src="<?php echo $js ?>backend.js"></script>
<script src="<?php echo $js ?>frontend.js"></script>
<script src="https://accounts.google.com/gsi/client" async></script>
<script src="https://cdn.jsdelivr.net/npm/jwt-decode@4.0.0/build/cjs/index.min.js"></script>
</body>

</html>