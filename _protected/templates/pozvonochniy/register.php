<body class="simple-page">
<div id="back-to-home">
    <a href="/" class="btn btn-outline btn-default"><i class="fa fa-home animated zoomIn"></i></a>
</div>
<div class="simple-page-wrap">
    <div class="simple-page-logo animated swing text-center">
        <a href="/">
            <!--<span><i class="fa fa-gg"></i></span>
            <span>Позвоночный</span>-->
			<img src="/assets/images/logo.png">
        </a>
    </div><!-- logo -->

    <div class="simple-page-form animated flipInY" id="signup-form">
        <h4 class="form-title m-b-xl text-center">Регистрация нового аккаунта</h4>
        <form action="/ajax.php" method="POST" class="form-ajax">
            <input type="hidden" name="register" value="1"/>

            <div class="form-group">
                <input name="first_name" type="text" class="form-control" placeholder="Имя" pattern="^[A-Za-zА-Яа-яЁё ]+$" required>
            </div>

            <div class="form-group">
                <input name="last_name" type="text" class="form-control" placeholder="Фамилия" pattern="^[A-Za-zА-Яа-яЁё ]+$" required>
            </div>

            <div class="form-group">
                <input name="phone" type="text" class="form-control phone-input" placeholder="Телефон" pattern="\+\d{10,14}" required>
            </div>

            <div class="form-group">
                <input name="email" type="email" class="form-control" placeholder="Email" required>
            </div>


            <div role="alert" class="alert alert-warning alert-dismissible form-ajax-message-wrapper">
                <button aria-label="Close" data-dismiss="alert" class="close" type="button"><span aria-hidden="true">×</span></button>
                <span class="form-ajax-message"></span>
            </div>

<!--            <div class="form-group m-b-xl">-->
<!--                <div class="checkbox checkbox-primary">-->
<!--                    <input type="checkbox" id="keep_me_logged_in"/>-->
<!--                    <label for="keep_me_logged_in">Keep me signed in</label>-->
<!--                </div>-->
<!--            </div>-->

            <input type="submit" class="btn btn-primary" value="ЗАРЕГИСТРИРОВАТЬСЯ">
        </form>
    </div><!-- #login-form -->

    <div class="simple-page-footer">
        <p>
            <small>У вас уже есть аккаунт ?</small>
            <a href="/login">ВОЙТИ</a>
        </p>
    </div><!-- .simple-page-footer -->


</div><!-- .simple-page-wrap -->
