<!-- APP NAVBAR ==========-->
<nav id="app-navbar" class="app-navbar p-l-lg p-r-md primary">
    <div id="navbar-header" class="pull-left">
        <button type="button" class="hamburger hidden-lg hamburger--spin js-hamburger" id="aside-toggle">
			<span class="hamburger-box">
				<span class="hamburger-inner"></span>
			</span>
        </button>

        <? if ($task): ?>
            <h5 id="page-title" class="visible-md-inline-block visible-lg-inline-block m-l-md">
                <a href="/">Мой кабинет</a>:
<!--                <a href="/tasks">Рассылки</a>:-->
                <a href="/task?id=<?= $task['id'] ?>">Рассылка #<?= $task['id'] ?></a>
            </h5>
        <? elseif ($list): ?>
            <h5 id="page-title" class="visible-md-inline-block visible-lg-inline-block m-l-md">
                <a href="/">Мой кабинет</a>:
                <a href="/lists">Списки</a>:
                <a href="/list?id=<?= $list['id'] ?>"> Список #<?= $list['id'] ?></a>
            </h5>
        <? else: ?>
            <h5 id="page-title" class="visible-md-inline-block visible-lg-inline-block m-l-md">
                <a href="/">Мой кабинет</a>:
                <a href="/<?= $page['url'] ?>"><?= $page['title'] ?></a>
            </h5>
        <? endif; ?>
    </div>

    <div>
        <ul id="top-nav" class="pull-right">

            <li class="nav-item dropdown">
                <form action="/ajax.php" method="POST" class="form-ajax">
                    <input type="hidden" name="logout" value="1"/>
                    <button type="submit"><i class="zmdi-hc-lg fa fa-sign-out"></i></button>
                </form>
            </li>
        </ul>
    </div>


</nav>
<!--========== END app navbar -->
