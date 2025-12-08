<?php
// =======================================================================
// РЕЖИМ ОТЛАДКИ: Включен
// =======================================================================
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// =======================================================================
// START CONFIG: Отредактируйте этот блок
// =======================================================================

// 1. Данные для подключения к Базе Данных
$db_host = 'localhost';      // Обычно 'localhost' на Beget
$db_name = 'fr7905kx_agt';   // Имя вашей базы данных (из SQL файла)
$db_user = 'fr7905kx_agt';  // ВАШЕ ИМЯ ПОЛЬЗОВАТЕЛЯ
$db_pass = 'Qw12er34as43!';  // ВАШ ПАРОЛЬ

// 2. Настройки CSV
$csv_separator = ';'; // Этот разделитель ВЕРНЫЙ, оставляем.

// =======================================================================
// CONFIG END: Дальше редактировать не нужно.
// =======================================================================

// --- Конфигурация импорта ---
// V5: Добавлено поле 'primary_csv_key' для пропуска мусорных строк
$import_config = [
    [
        'file' => 'CF.csv',
        'table' => 'tbl_cloudflare_accounts',
        'primary_csv_key' => 'cf_email', // Главный столбец для этой таблицы
        'column_map' => ['cf_email' => 'cf_email', 'cf_pass' => 'cf_password', 'cf_api_key' => 'cf_api_key', 'status' => 'status'],
        'fk_lookups' => []
    ],
    [
        'file' => 'Шаблоны.csv',
        'table' => 'tbl_templates',
        'primary_csv_key' => 'Шаблон',
        'column_map' => ['Шаблон' => 'template_name', 'Шаблон_на_сервере' => 'server_name', 'URL_кнопки' => 'button_url', 'json_template' => 'json_config'],
        'fk_lookups' => []
    ],
    [
        'file' => 'ЯВМ_GSC.csv',
        'table' => 'tbl_sites',
        'primary_csv_key' => 'URL домена', // V5: Если он пустой - пропускаем
        'column_map' => ['URL домена' => 'domain_url', 'Регистратор' => 'registrar', 'Вебмастер' => 'webmaster', 'cf_email' => 'cf_email', 'Шаблон' => 'template_name', 'Статус_Регистрации' => 'status_registration', 'Статус_CF' => 'status_cf', 'Статус_NS_Update' => 'status_ns_update', 'NS1' => 'ns1', 'NS2' => 'ns2', 'NS_Status' => 'ns_status', 'Статус_Прокси' => 'status_proxy', 'Дата размещения' => 'publish_date', 'G_TXT_Status' => 'gsc_status', 'Y_TXT_Status' => 'yvm_status'],
        'fk_lookups' => []
    ],
    [
        'file' => 'Рабочий.csv',
        'table' => 'tbl_tasks',
        'primary_csv_key' => 'Номер', // V5: Если он пустой - пропускаем
        'column_map' => ['Номер' => 'original_task_num', 'Главный ключ' => 'main_keyword', 'Тип страницы' => 'page_type', 'Ключи' => 'keywords_lsi', 'URLs конкурентов' => 'competitor_urls', 'Структуры конкурентов' => 'competitor_structures', 'Статус' => 'task_status', 'query' => 'query', 'Комментарий' => 'comment', 'h1' => 'target_h1', 'Title' => 'target_title', 'Descr' => 'target_description', 'URL' => 'target_url_path', 'Язык - уже созданые изменять нельзя' => 'language'],
        'fk_lookups' => [
            'site_id' => [
                'lookup_table' => 'tbl_sites', 
                'lookup_csv_header' => 'Сайт', 
                'lookup_db_column' => 'domain_url', 
                'return_column' => 'site_id'
            ]
        ]
    ],
    [
        'file' => 'Готовые тексты.csv',
        'table' => 'tbl_articles',
        'primary_csv_key' => 'title', // V5: Будем считать, что если у статьи нет 'title', то это мусор
        'column_map' => ['title' => 'article_title', 'desc' => 'article_description', 'Готово' => 'article_html_content', 'Оценка' => 'rating', 'Статус' => 'article_status', 'query' => 'generation_query', 'Нейронки' => 'generation_model'],
        'fk_lookups' => [
            'task_id' => [
                'lookup_table' => 'tbl_tasks', 
                'lookup_csv_header' => '№', // '№' из Готовые тексты.csv
                'lookup_db_column' => 'original_task_num', 
                'return_column' => 'task_id'
            ]
        ]
    ],
];

// =======================================================================
// КОД СКРИПТА (не редактировать)
// =======================================================================

ini_set('memory_limit', '1024M');
set_time_limit(0);

echo "--- [НАЧАЛО РАБОТЫ v5.1 (Мягкий импорт, исправлено)] ---" . PHP_EOL;
echo "Разделитель CSV установлен: '" . $csv_separator . "'" . PHP_EOL;

// --- Подключение к БД ---
try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    echo "✅ [OK] Подключен к БД $db_name" . PHP_EOL;
} catch (\Throwable $e) {
    die("❌ [ОШИБКА] Не могу подключиться к БД: " . $e->getMessage() . PHP_EOL);
}

// --- Кэш для внешних ключей ---
$fk_cache = [];

// --- Начинаем импорт по порядку ---
foreach ($import_config as $task) {
    $file_path = __DIR__ . '/' . $task['file'];
    $table = $task['table'];
    
    echo "---" . PHP_EOL;
    echo "➡️  Обработка: {$task['file']}  ->  Таблица: $table" . PHP_EOL;

    if (!file_exists($file_path)) {
        echo "   ❌ [ОШИБКА] Файл не найден: $file_path (пропущено)" . PHP_EOL;
        continue;
    }

    try {
        echo "   ...[DEBUG] Открываю файл: $file_path" . PHP_EOL;
        $file_handle = fopen($file_path, 'r');
        if ($file_handle === false) {
            throw new Exception("Не удалось открыть файл $file_path");
        }
        
        echo "   ...[DEBUG] Применяю авто-конвертер кодировки (WINDOWS-1251 -> UTF-8)..." . PHP_EOL;
        stream_filter_append($file_handle, 'convert.iconv.windows-1251/utf-8');

        // --- Читаем заголовки CSV ---
        echo "   ...[DEBUG] Читаю заголовки (первую строку)..." . PHP_EOL;
        $headers_csv = fgetcsv($file_handle, 0, $csv_separator);
        if ($headers_csv === false || $headers_csv === null) {
            throw new Exception("Не удалось прочитать заголовки из $file_path.");
        }
        
        $headers_clean = [];
        foreach ($headers_csv as $i => $header) {
            $headers_clean[] = trim($header); 
        }
        
        $header_map = array_flip($headers_clean);
        echo "   ...[DEBUG] Заголовки найдены: " . implode(' | ', $headers_clean) . PHP_EOL;
        
        // --- Готовим SQL запросы ---
        $db_columns_to_insert = []; 
        $csv_headers_to_read = [];  
        
        // 1. Внешние ключи (FK)
        $fk_stmts = [];
        foreach ($task['fk_lookups'] as $db_col => $fk_config) {
            if (!isset($header_map[$fk_config['lookup_csv_header']])) {
                 throw new Exception("Критическая ошибка (FK): Не найден столбец '{$fk_config['lookup_csv_header']}'");
            }
            $db_columns_to_insert[] = "`$db_col`";
            $sql_fk = sprintf("SELECT `%s` FROM `%s` WHERE `%s` = ?", $fk_config['return_column'], $fk_config['lookup_table'], $fk_config['lookup_db_column']);
            $fk_stmts[$db_col] = $pdo->prepare($sql_fk);
        }
        
        // 2. Прямое сопоставление (Column Map)
        foreach ($task['column_map'] as $csv_header => $db_col) {
             if (!isset($header_map[$csv_header])) {
                 throw new Exception("Критическая ошибка (Map): Не найден столбец '$csv_header'.");
             }
             $db_columns_to_insert[] = "`$db_col`";
             $csv_headers_to_read[] = $csv_header;
        }

        $sql_insert = sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s)",
            $table,
            implode(', ', $db_columns_to_insert),
            implode(', ', array_fill(0, count($db_columns_to_insert), '?'))
        );
        
        echo "   ...[DEBUG] Готовлю SQL: $sql_insert" . PHP_EOL;
        $stmt_insert = $pdo->prepare($sql_insert);

        // --- Читаем CSV построчно ---
        echo "   ...[DEBUG] Начинаю транзакцию и чтение строк..." . PHP_EOL;
        $pdo->beginTransaction();
        
        $row_number = 1;
        $imported_count = 0;
        $skipped_count = 0;
        $skipped_silently = 0; 
        $warning_count = 0;

        // V5: Проверяем, есть ли главный ключ
        $primary_key_index = null;
        if (isset($task['primary_csv_key']) && isset($header_map[$task['primary_csv_key']])) {
            $primary_key_index = $header_map[$task['primary_csv_key']];
            echo "   ...[DEBUG] Проверка строк будет по главному ключу: '{$task['primary_csv_key']}'" . PHP_EOL;
        }

        while (($row_data = fgetcsv($file_handle, 0, $csv_separator)) !== false) {
            $row_number++;
            $params_to_execute = [];

            try {
                // V5: Пропускаем мусорные строки, где главный ключ пуст
                if ($primary_key_index !== null && empty($row_data[$primary_key_index])) {
                    $skipped_silently++;
                    continue;
                }
                
                if (count($row_data) < count($header_map)) {
                     if (count($row_data) === 1 && empty($row_data[0])) {
                        $skipped_silently++; // Это совсем пустая строка
                        continue; 
                     }
                     throw new Exception("Кол-во столбцов в строке (" . count($row_data) . ") меньше, чем в заголовке (" . count($header_map) . ").");
                }

                // 1. Собираем значения FK
                foreach ($task['fk_lookups'] as $db_col => $fk_config) {
                    $lookup_value_csv = $row_data[$header_map[$fk_config['lookup_csv_header']]];
                    
                    if ($lookup_value_csv === null || $lookup_value_csv === '') {
                        // V5: Ключ для поиска пуст, просто вставляем NULL
                        $params_to_execute[] = null;
                        continue;
                    }
                    
                    $cache_key = $fk_config['lookup_table'] . '_' . $lookup_value_csv;
                    if (isset($fk_cache[$cache_key])) {
                        $fk_id = $fk_cache[$cache_key];
                    } else {
                        $fk_stmts[$db_col]->execute([$lookup_value_csv]);
                        $fk_id = $fk_stmts[$db_col]->fetchColumn();
                        $fk_cache[$cache_key] = $fk_id;
                    }
                    
                    if ($fk_id === false) {
                        // V5: Ключ НЕ НАЙДЕН. Не кидаем ошибку, а пишем Warning и вставляем NULL
                        echo "   ⚠️  [Строка $row_number] ПРЕДУПРЕЖДЕНИЕ: Не удалось найти ID для '{$fk_config['lookup_csv_header']}' = '$lookup_value_csv'. Поле будет пустым (NULL)." . PHP_EOL;
                        $warning_count++;
                        $params_to_execute[] = null;
                        $fk_cache[$cache_key] = null; // Кэшируем "не найдено"
                    } else {
                        $params_to_execute[] = $fk_id;
                    }
                }

                // 2. Собираем простые значения
                foreach ($csv_headers_to_read as $csv_header) {
                    if (!isset($row_data[$header_map[$csv_header]])) {
                        throw new Exception("Попытка прочитать несуществующий столбец '$csv_header'");
                    }
                    $value = $row_data[$header_map[$csv_header]];
                    if ($value === '' || $value === 'NaN' || $value === 'NaN') {
                        $params_to_execute[] = null;
                    } else {
                        $params_to_execute[] = $value;
                    }
                }
                
                // 3. Выполняем INSERT
                $stmt_insert->execute($params_to_execute);
                $imported_count++;
                
            } catch (\Throwable $e) { // V5.1: Упрощенный единый отлов ошибок
                
                // "Мягко" ловим ошибки Constraint (FK)
                if ($e instanceof \PDOException && $e->getCode() == 23000) { 
                    echo "   ⚠️  [Строка $row_number] ПРЕДУПРЕЖДЕНИЕ (FK): " . $e->getMessage() . " (пропущено)" . PHP_EOL;
                    $warning_count++;
                } else {
                     // Это настоящая ошибка
                    echo "   ❌ [Строка $row_number] ОШИБКА: " . $e->getMessage() . " (пропущено)" . PHP_EOL;
                    $skipped_count++;
                }
                continue; // Пропускаем эту строку и идем к следующей
            }
        }
        
        echo "   ...[DEBUG] Чтение файла завершено. Закрываю файл..." . PHP_EOL;
        fclose($file_handle);
        
        echo "   ...[DEBUG] Фиксирую изменения (Commit)..." . PHP_EOL;
        $pdo->commit();
        
        echo "   ✅ [ГОТОВО] Файл {$task['file']} обработан." . PHP_EOL;
        echo "      Импортировано строк: $imported_count" . PHP_EOL;
        if ($skipped_silently > 0) {
            echo "      Пропущено пустых строк: $skipped_silently" . PHP_EOL;
        }
        if ($warning_count > 0) {
            echo "      Строк с предупреждениями (битые FK): $warning_count" . PHP_EOL;
        }
        if ($skipped_count > 0) {
            echo "      Пропущено (с ошибками): $skipped_count" . PHP_EOL;
        }

    } catch (\Throwable $e) { // Ловим КРИТИЧЕСКИЕ ошибки (на уровне файла)
        $pdo->rollBack(); 
        echo "   ❌ [КРИТИЧЕСКАЯ ОШИБКА] Файл {$task['file']}: " . $e->getMessage() . PHP_EOL;
        echo "   ...[DEBUG] Stack Trace: " . $e->getTraceAsString() . PHP_EOL;
    }
}

echo "---" . PHP_EOL;
echo "🎉 Вся работа завершена!" . PHP_EOL;

?>