<?php


declare(strict_types=1);

namespace Core;


class View
{
    public static function render($view, $args = [])
    {
        extract($args, EXTR_SKIP);

        $file = '../App/Views/'.$view; // relative to Core dir

        if (is_readable($file)) {
            require $file;
        } else {
            throw new \Exception("{$file} not found");
        }
    }

    public static function renderTemplate($template, $args = [])
    {
        static $twig = null;

        if ($twig === null) {
            $loader = new \Twig_Loader_Filesystem('../App/Views');
            $twig = new \Twig_Environment($loader);

            // UI text for our base.html
            $twig->addGlobal('APP_NAME', "File Lister");
            $twig->addGlobal('HOME', "My files");
            $twig->addGlobal('ADD_FILE', "Add file");
            $twig->addGlobal('DOWNLOAD_FILE', "Download file");
            $twig->addGlobal('LOG_OUT', "Log out");
            $twig->addGlobal('RECENT_QUERIES', "Queries made just now");
            $twig->addGlobal('UI_TXT_QUERY_TOTAL_TIME', "Total execution time: ");
            $twig->addGlobal('UI_TXT_QUERY_MIN_TIME', "Min execution time: ");
            $twig->addGlobal('UI_TXT_QUERY_MAX_TIME', "Max execution time: ");
            $twig->addGlobal('UI_TXT_QUERIES_HISTORY_LOCATED', "History of all queries is in ");
            $twig->addGlobal('UI_TXT_FOOTER_COPYRIGHT', "Ilya Kushlianski © 2018. EPAM PHP05 training");
            $twig->addGlobal("UI_TXT_PREP_DOWNLOAD", "Preparing for download...");
        }

        echo $twig->render($template, $args);
    }
}