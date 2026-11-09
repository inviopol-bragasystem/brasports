<?php

$functions[] = new Twig_SimpleFunction('base_url', function () {
    return base_url();
});

$functions[] = new Twig_SimpleFunction('domain', function () {
    echo $_SERVER['HTTP_HOST'];
});

$functions[] = new Twig_SimpleFunction('seo_header', function (bool $responsive = true) {
    echo \app\helpers\Seo::displayHeader($responsive);
});

$functions[] = new Twig_SimpleFunction('url_referer', function () {
    echo url_referer();
});

$functions[] = new Twig_SimpleFunction('dd', function (...$args) {
    echo '<pre>' . print_r($args, true) . '</pre>';
});

$functions[] = new Twig_SimpleFunction('preg_replace', function ($pattern, $replacement, $subject) {
    return preg_replace($pattern, $replacement, $subject);
});

$functions[] = new Twig_SimpleFunction('json', function ($data) {
    if ($data instanceof \app\core\ValueObject) {
        echo $data->toJson(true);
    } else {
        echo json_encode($data);
    }
});

$functions[] = new Twig_SimpleFunction('resumoJogos', function () {
    return \app\models\JogosModel::getResumoJogos();
});

$functions[] = new Twig_SimpleFunction('fncSinal', function ($valor, $isReal = false) {
    $formatado = \app\helpers\Number::real(abs($valor));
    $sinal = $valor < 0 ? '-' : ($valor == 0 ? '' : '+');
    $prefixo = $isReal ? "{$sinal} R$ " : "{$sinal}";
    return $prefixo . $formatado;
});

$functions[] = new Twig_SimpleFunction('config', function (string $key) {
    return app_config($key);
});

$functions[] = new Twig_SimpleFunction('url', function (string $controller = null, array $vars = null, $module = null, array $get = null) {
    return url($controller, $vars, $module, $get);
});

$functions[] = new Twig_SimpleFunction('banners', function (string $ref, string $data = null, int $dia = null, int $limit = 5) {
    return \app\models\helpers\BannersModel::get($ref, $data ?: date('Y-m-d'), $dia == null ? (int)date('w') : $dia, $limit);
});

$functions[] = new Twig_SimpleFunction('destaquesJogosHome', function (int $limit = 6) {
    return \app\models\JogosModel::getJogosDestaqueHome($limit);
});

$functions[] = new Twig_SimpleFunction('destaquesCassinoHome', function (int $limit = 6) {
    if (!\app\models\DadosModel::get()->getExibirCassino()) {
        return [];
    }
    $all = \app\models\CassinoModel::getGames(true);
    $featured = [];
    foreach ($all as $j) {
        if (!empty($j['featured']) || in_array($j['game_code'], ['fortune-tiger', 'fortune-ox', 'fortune-rabbit', 'fortune-mouse', 'treasures-of-aztec', 'fortune-dragon'])) {
            $featured[] = $j;
            if (count($featured) >= $limit) {
                break;
            }
        }
    }
    if (empty($featured) && !empty($all)) {
        $featured = array_slice($all, 0, $limit);
    }
    return $featured;
});

$functions[] = new Twig_SimpleFunction('inputFile', function (string $caption, string $name, string $accept = null, string $class = 'btn-success', array $attr = null) {
    echo formButtonFile($caption, $name, $accept, $class, $attr);
});

$functions[] = new Twig_SimpleFunction('pagina', function (string $ref, int $refId = 0, bool $autoCreate = true) {
    return \app\models\helpers\PaginasModel::getByRef($ref, $refId, $autoCreate);
});

$functions[] = new Twig_SimpleFunction('paginas', function (string $ref, int $refId = 0) {
    return \app\models\helpers\PaginasModel::listRef($ref, $refId);
});

$functions[] = new Twig_SimpleFunction('seo_footer', function () {
    echo \app\helpers\Seo::displayFooter();
});

$functions[] = new Twig_SimpleFunction('resumoBancario', function (\app\vo\UserVO $user = null) {
    return \app\models\HistoricoBancarioModel::getResumo($user ?: \app\modules\admin\Admin::getLogged());
});

$functions[] = new Twig_SimpleFunction('difDays', function (string $dt1, string $dt2) {
    $dt1 = new DateTime($dt1);
    $dif = $dt1->diff(new DateTime($dt2));
    return $dif->days;
});

$functions[] = new Twig_SimpleFunction('comunicados', function () {
    return \app\models\ComunicadosModel::getComunicados(\app\modules\admin\Admin::getLogged());
});

$functions[] = new Twig_SimpleFunction('admMenu', function (?string $module = null) {
    if (empty($module)) {
        $module = \app\APP::getCurrentModule() ?: 'admin';
    }


    $logged = \app\modules\admin\Admin::getLogged();
    $userType = $logged ? $logged->getType() : 1;
    $menu = \app\models\admin\MenuModel::getMenu($userType, $module, 1);

    $fncMenu = function (int $root = 0) use ($menu, &$fncMenu, $module) {

        $html = '';

        foreach ($menu as $m) {
            if ($m->getRoot() == $root) {

                $droplink = $submenu = $arrow = $menuicon = '';

                if (!$root) {
                    $menuicon = 'menu-icon';
                } else {
                    $menuicon = 'submenu-icon';
                }

                if ($m->getController()) {
                    $link = "href='{$module}/{$m->getController()}/PAGE{$m->getToken()}'";
                } else if ($m->getOnClick()) {
                    $onclick = htmlspecialchars($m->getOnClick());
                    $link = "href=\"javascript:{$onclick}; return false;\"";
                } else {
                    $link = 'href="javascript:;"';
                    $submenu = $fncMenu($m->getId());
                    if ($submenu) {
                        $droplink = 'droplink';
                        $submenu = "<ul class='sub-menu'>{$submenu}</ul>";
                        $arrow = '<span class="arrow"></span>';
                    }
                }

                if (!$root) {
                    $link .= ' class="waves-effect waves-button"';
                }

                $html .= <<<HTML
<li class="{$droplink}">
    <a {$link} >
        <span class="{$menuicon} {$m->getIcone()} m-r-10"></span> <p>{$m->getTitle()}</p> {$arrow}
    </a>
    {$submenu}
</li>
HTML;
            }
        }

        return $html;
    };

    echo $fncMenu();

});

$functions[] = new Twig_SimpleFunction('optMeses', function () {
    foreach (range(1, 12) as $i) {
        echo formOption(\app\helpers\Date::getMonthName($i), $i);
    }
});

$functions[] = new Twig_SimpleFunction('optBancos', function () {
    echo \app\models\financeiro\SaquesModel::optionsBancos();
});

$functions[] = new Twig_SimpleFunction('options', function (string $ref, bool $orderby = null) {
    echo \app\models\helpers\OptionsModel::options($ref, $orderby);
});

$functions[] = new Twig_SimpleFunction('optUsers', function (int $type = null) {
    echo \app\models\UsersModel::options($type ? [$type] : null);
});

$functions[] = new Twig_SimpleFunction('optEstados', function (string $label = 'title') {
    echo \app\models\EstadosModel::options($label);
});

$functions[] = new Twig_SimpleFunction('user', function (string $method = null, bool $format = false) {
    $user = \app\modules\admin\Admin::getLogged();
    if ($user) {
        if ($method) {
            return $user->get($method, $format);
        } else {
            return $user;
        }
    }
    return null;
});

$functions[] = new Twig_SimpleFunction('dados', function (string $method = null, bool $format = false) {
    $dados = \app\models\DadosModel::get();
    if ($method) {
        return $dados->get($method, $format);
    } else {
        return $dados;
    }
});

return $functions;