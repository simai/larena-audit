<?php

declare(strict_types=1);

return [
    'title' => 'История действий',
    'eyebrow' => 'Операции',
    'heading' => 'История действий',
    'description' => 'Здесь показаны сохранённые действия с контентом и доступом. Чувствительные поля не отображаются.',
    'navigation' => ['history' => 'История действий'],
    'region_label' => 'История действий',
    'empty_title' => 'Действий со страницами пока нет',
    'empty_description' => 'Создайте или измените страницу, и запись появится здесь. Здесь же появятся действия с доступом.',
    'columns' => ['operation' => 'Операция', 'subject' => 'Объект', 'actor' => 'Пользователь', 'detail' => 'Безопасные детали', 'time' => 'Время'],
    'operations' => ['Created' => 'Создание', 'Updated' => 'Изменение', 'Published' => 'Публикация', 'Unpublished' => 'Снятие с публикации', 'Submitted for publication' => 'Передано на публикацию', 'Restored' => 'Восстановление версии', 'Permission denied' => 'Доступ запрещён', 'File uploaded'=>'Файл загружен','File metadata updated'=>'Метаданные файла изменены','File deleted'=>'Файл удалён','File used on page'=>'Файл добавлен на страницу','Page activity' => 'Действие со страницей', 'Security activity' => 'Действие безопасности', 'Record created' => 'Запись создана', 'Record updated' => 'Запись изменена', 'Record archived' => 'Запись перемещена в архив', 'Record restored' => 'Запись восстановлена', 'Record purged' => 'Запись удалена навсегда', 'Record history pruned' => 'История записи сокращена', 'Structure archived' => 'Раздел перемещён в архив', 'Structure restored' => 'Раздел восстановлен', 'Structure purged' => 'Раздел удалён навсегда', 'Structure history limit changed' => 'Изменено число хранимых версий раздела', 'Schema created' => 'Схема создана', 'Schema version created' => 'Создана новая версия схемы', 'Schema version rejected' => 'Новая версия схемы отклонена', 'Record published' => 'Запись опубликована', 'Record unpublished' => 'Запись снята с публикации', 'Publication scheduled' => 'Публикация запланирована', 'Publication archived' => 'Публикация перемещена в архив', 'Scheduled publications applied' => 'Запланированные публикации выполнены', 'Storage activity' => 'Действие с данными'],
    'statuses' => ['allowed' => 'Разрешено', 'denied' => 'Запрещено', 'draft' => 'Черновик', 'published' => 'Опубликовано'],
    'version' => 'версия :version',
    'not_recorded' => 'Не записано',
];
