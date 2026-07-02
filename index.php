<?php
// Prevent worker script termination when a client connection is interrupted
require __DIR__.'/vendor/autoload.php';
session_start();
use App\Core\{Routes, Page};
use App\Services\{DB, GalleryConfig};
use Symfony\Component\Yaml\Yaml;
use Tracy\Debugger;

class App
{
    public static function start()
    {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        error_reporting(E_ALL);

        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/ngallery.yaml')) {
            $ngallery = Yaml::parse(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/ngallery.yaml'))['ngallery'];
            $ngallery = GalleryConfig::applyAuthOverlay($ngallery);
            $ngallery = GalleryConfig::applyServerOverlay($ngallery);
            define('NGALLERY', $ngallery);
            define("NGALLERY_TASKS", Yaml::parse(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/app/Controllers/Exec/Tasks/ngallery-tasks.yaml'))['tasks']);
            \App\Services\Date::applySiteTimezone();
            if (NGALLERY['root']['debug'] === true) {
                $logDir = $_SERVER['DOCUMENT_ROOT'] . (NGALLERY['root']['logslocation'] ?? '/logs');
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0775, true);
                }
                Debugger::enable(Debugger::DEVELOPMENT, $logDir);
            }
            try {

                if (NGALLERY['root']['maintenance'] === false) {
                    DB::init([
                        'driver' => 'mysql',
                        'host' => NGALLERY['root']['db']['host'],
                        'database' => NGALLERY['root']['db']['name'],
                        'username' => NGALLERY['root']['db']['login'],
                        'password' => NGALLERY['root']['db']['password'],
                    ]);
                    Routes::init();
                } else {
                    Page::set('Errors/ServerDown');
                }
            } catch (PDOException $ex) {
                echo '<details><summary class="p20 s5" style="border:none; margin:0 -20px"><b>Произошла ошибка MySQL</b></summary>'.nl2br($ex).'</details>';
            } catch (Exception $ex) {
                echo '<pre><b>Произошла скриптовая ошибка PHP</b><br><br>'.nl2br($ex).'</pre>';
            }
        } else {
            Page::set('Errors/Problems');
        }
    }
}

App::start();