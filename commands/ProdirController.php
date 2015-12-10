<?php

namespace app\commands;

use yii\console\Controller;

class ProdirController extends Controller
{
    private $saveDir = '/opt/dev/prodir.grab/data/';

    /**
     * @var string Путь до картинок конструктора
     */
    private $permanentPath = 'http://www.prodir.com/configurator/services/assets90_new/';

    /**
     * @var array Идщники вида
     */
    private $views = [9, 10, 11];

    /**
     * Пиздим картинки из продировского конструктора
     */
    public function actionIndex()
    {
        // Проверяем диру на запись
        if (!is_writable($this->saveDir)) {
            echo 'Дирктория ' . $this->saveDir . ' недоступна для записи' . PHP_EOL
                . 'Измените свойство saveDir в исполняемом классе ('
                . __CLASS__
                . ') и убедитесь что директория существует и у неё достаточно прав на запись' . PHP_EOL;
            return Controller::EXIT_CODE_ERROR;
        }

        // Подгружаем дерево
        $tree = require(__DIR__ . '/../data/tree.php');
        //var_dump($tree);

        // Собираем все ссылки для закачки
        $links = [];
        foreach ($tree as $model => $property) {
            if (!isset($property['legend'])) {
                echo 'У ' . $model . ' нет легенды' . PHP_EOL;
                return Controller::EXIT_CODE_ERROR;
            }

            if (!isset($property['colors'])) {
                echo 'У ' . $model . ' нет цветов' . PHP_EOL;
                return Controller::EXIT_CODE_ERROR;
            }

            if (count($property['legend']) !== count($property['colors'])) {
                echo 'У ' . $model . ' разное кол-во элементов в легенде и цветовых схем' . PHP_EOL;
                return Controller::EXIT_CODE_ERROR;
            }

            foreach ($property['legend'] as $subdir => $slug) {
                // Перебераем цвета
                for ($i = 0; $i < count($property['legend']); $i++) {
                    foreach ($property['colors'][$i] as $code => $digits) {
                        foreach ($digits as $colorId) {
                            foreach ($this->views as $variant) {
                                $grabUrl = $this->permanentPath . $model . '/' . $slug . '_' . $code . '_' . $colorId . '_' . $variant . '.png';
                                $saveDir = $this->saveDir . $model . '/' . $subdir . '/';
                                if (!is_dir($saveDir)) {
                                    //echo 'Создаю директорию: ' . $saveDir . PHP_EOL;
                                    mkdir($saveDir, 0777, true);
                                }

                                $saveFile = $code . '_' . $colorId . '_' . $variant . '.png';

                                $links[$grabUrl] = $saveDir . $saveFile;
                            }
                        }
                    }
                }
            }
        }
        $cnt = count($links);
        echo 'Всего готово ' . $cnt . ' ссылок на закачку' . PHP_EOL;
        echo 'Начинаем скачивать картинки с prodir.com' . PHP_EOL;

        foreach ($links as $url => $path) {
            usleep(200);
            exec('wget -q ' . $url . ' -O ' . $path, $result);
            if ($result) {
                echo PHP_EOL . $url . ' СЛОМАНА!!!' . PHP_EOL;
            } else {
                echo '.';
            }
        }

        echo 'Всё заебись' . PHP_EOL;
        //echo PHP_EOL;
        Controller::EXIT_CODE_NORMAL;
    }
}
