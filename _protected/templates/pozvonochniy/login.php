<body class="simple-page">

<div id="back-to-home">
    <a href="/" class="btn btn-outline btn-default"><i class="fa fa-home animated zoomIn"></i></a>
</div>
<div class="simple-page-wrap">
    <div class="simple-page-logo animated swing text-center">
        <a href="/">
			<img src="/assets/images/logo.png">
            <!--<span><i class="fa fa-gg"></i></span>
            <span>Позвоночный</span>-->
        </a>
    </div><!-- logo -->
    <div class="simple-page-form animated flipInY" id="login-form">
        <h4 class="form-title m-b-xl text-center">Войти в личный кабинет</h4>
        <form action="/ajax.php" method="POST" class="form-ajax">
            <input type="hidden" name="login" value="1"/>

            <div class="form-group">
                <input name="phone" type="text" class="form-control phone-input" placeholder="Phone"  pattern="\+\d{10,14}" required>
            </div>

            <div class="form-group">
                <input name="password" type="password" class="form-control" placeholder="Password" pattern="[\S]{6,20}"  required>
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
            <input type="submit" class="btn btn-primary" value="Войти">
        </form>
    </div><!-- #login-form -->

    <div class="simple-page-footer">
        <p><a href="/pasrecover">ЗАБЫЛИ ПАРОЛЬ?</a></p>
        <p>
            <small>У вас еще нет аккаунта ?</small>
            <a href="/register">ЗАРЕГИСТРИРОВАТЬСЯ</a>
        </p>
    </div><!-- .simple-page-footer -->


</div><!-- .simple-page-wrap -->
