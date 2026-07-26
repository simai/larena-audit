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
    'operations' => ['Created' => 'Создание', 'Updated' => 'Изменение', 'Published' => 'Публикация', 'Unpublished' => 'Снятие с публикации', 'Submitted for publication' => 'Передано на публикацию', 'Restored' => 'Восстановление версии', 'Permission denied' => 'Доступ запрещён', 'File uploaded'=>'Файл загружен','File metadata updated'=>'Метаданные файла изменены','File deleted'=>'Файл удалён','File used on page'=>'Файл добавлен на страницу','Page activity' => 'Действие со страницей', 'Security activity' => 'Действие безопасности'],
    'statuses' => ['allowed' => 'Разрешено', 'denied' => 'Запрещено', 'draft' => 'Черновик', 'published' => 'Опубликовано'],
    'version' => 'версия :version',
    'not_recorded' => 'Не записано',
];
