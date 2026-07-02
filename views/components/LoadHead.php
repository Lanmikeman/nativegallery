<?php
use \App\Services\{ThemeManager, Auth, AudioLibrary};

require_once $_SERVER['DOCUMENT_ROOT'] . '/views/components/AssetHelper.php';

$themeManager = new ThemeManager();
$themeManager->loadThemes();

$stylesheet = $themeManager->getThemeStylesheet();
$musicUserLoggedIn = Auth::userid() > 0;
$musicEnabled = $musicUserLoggedIn && AudioLibrary::isEnabled();
?>

<meta http-equiv="content-type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title><?=NGALLERY['root']['title']?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=PT+Sans+Narrow:wght@400;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/static/css/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= ng_asset('/static/css/style.css') ?>">
    <link rel="stylesheet" href="<?= ng_asset('/static/css/desktop.css') ?>">
    <link rel="stylesheet" href="<?= ng_asset('/static/css/trans.css') ?>">
    <link rel="stylesheet" href="<?= ng_asset('/static/css/photo.css') ?>">
    <link rel="stylesheet" href="<?= ng_asset('/static/css/notie.css') ?>">
    <link rel="stylesheet" href="<?= ng_asset('/static/css/comments.css') ?>">
    <script src="<?= ng_asset('/static/js/comments.js') ?>" data-restart></script>
    <?php
    if ($stylesheet) { ?>
    <link rel="stylesheet" href="<?= ng_asset($stylesheet) ?>">
    <?php } ?>
    <link rel="stylesheet" href="<?= ng_asset('/static/css/map.css') ?>">
    <link rel="stylesheet" href="<?= ng_asset('/static/css/jquery-ui-1.8.20.custom.css') ?>">
    <link rel="stylesheet" href="<?= ng_asset('/static/css/progress.css') ?>">
    <?php if ($musicEnabled) { ?>
    <link rel="stylesheet" href="<?= ng_asset('/static/css/music-player.css') ?>">
    <?php } ?>
    <script src="<?= ng_asset('/static/js/jquery.js') ?>" data-restart></script>
    <script src="<?= ng_asset('/static/js/jquery.form.min.js') ?>" data-restart></script>
    <script src="<?= ng_asset('/static/js/core.js') ?>" data-restart></script>
    <script src="<?= ng_asset('/static/js/index.js') ?>" data-restart></script>
    <script src="<?= ng_asset('/static/js/jquery-ui.js') ?>" data-restart></script>
    <script src="<?= ng_asset('/static/js/selector.js') ?>" data-restart></script>
    <script src="<?= ng_asset('/static/js/selector2.js') ?>" data-restart></script>
    <script src="<?= ng_asset('/static/js/imageupload.js') ?>" data-restart></script>
    <script src="<?= ng_asset('/static/js/progress.js') ?>" data-restart></script>
    <script src="<?= ng_asset('/static/js/notie.js') ?>" data-restart></script>
    <?php if ($musicEnabled) { ?>
    <script src="<?= ng_asset('/static/js/music-player.js') ?>"></script>
    <?php } ?>
    <?php if ($musicUserLoggedIn) { ?>
    <script src="<?= ng_asset('/static/js/routing.js') ?>"></script>
    <?php } ?>
    <script src="<?= ng_asset('/static/js/photo.js') ?>" data-restart></script>
    <script src="<?= ng_asset('/static/js/newcore.js') ?>" data-restart></script>
    <script src="<?= ng_asset('/static/js/act.js') ?>" data-restart></script>
    <script src="<?= ng_asset('/static/js/core_lk.js') ?>" data-restart></script>
    <script src="<?= ng_asset('/static/js/tablesort.js') ?>" data-restart></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
    <div class="progress-container fixed-top">
<span class="progress-bard"></span>
</div>
<style>

        .progress-container {
  width: 100%;
  background:linear-gradient(rgba(0,0,0,0.2),rgba(0,0,0,0.2)) var(--theme-bg-color); 
  height: 5px;
  display: block;
}

@media screen and (max-width: 768px) {
                                :root {
                                    --bckgr: -1500px 0;
                                    --bckgr2: 1500px 0;
                                }
                            }

                            @media screen and (min-width: 768px) {
                                :root {
                                    --bckgr: -3500px 0;
                                    --bckgr2: 3500px 0;
                                }
                            }
@-webkit-keyframes bg-move {
  0%   { background-position: var(--bckgr); }
  100% { background-position: var(--bckgr2); }
}
@keyframes bg-move {
0%   { background-position: var(--bckgr); }
  100% { background-position: var(--bckgr2); }
}

.progress-bard {
  background-color: #fff; 
  width: 0%;
  display: block;
  height: inherit;
  transition: width 0.6s ease;
  background-image: linear-gradient(270deg, rgba(100, 181, 239, 0) 48.44%,  var(--theme-bg-hover-color) 75.52%, rgba(100, 181, 239, 0) 100%);
    background-repeat: no-repeat;
  animation: bg-move linear 2s infinite;
}
  
</style>
<script>
    notie.setOptions({
    transitionCurve: 'cubic-bezier(0.2, 0, 0.2, 1)'
});
var Notify =  {
    noty: function(status, text) {

        if (status == 'danger') status = 'error';

        return notie.alert({ type: status, text: text })

    },
}
function scrollProgressBarWidth(number) {
                var getMax = function() {
                    return $(document).height() - $(window).height();
                };
                var progressBar = $(".progress-bard"),
                    max = getMax(),
                    value,
                    width;

                var setWidth = function() {
                    progressBar.css({
                        width: number + '%'
                    });
                };

                setWidth();
            }
            
function escapeHtml(text) {
    var map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
  }
</script>
