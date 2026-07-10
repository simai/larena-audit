<?php

declare(strict_types=1);

return [
    'title' => 'История действий',
    'eyebrow' => 'Операции',
    'heading' => 'История действий',
    'description' => 'Здесь показаны сохранённые действия со страницами. Тексты страниц и чувствительные поля не отображаются.',
    'navigation' => ['history' => 'История действий'],
    'region_label' => 'История действий со страницами',
    'empty_title' => 'Действий со страницами пока нет',
    'empty_description' => 'Создайте или измените страницу, и запись появится здесь.',
    'columns' => ['operation' => 'Операция', 'page' => 'Страница', 'actor' => 'Пользователь', 'status' => 'Статус', 'time' => 'Время'],
    'operations' => ['Created' => 'Создание', 'Updated' => 'Изменение', 'Published' => 'Публикация', 'Unpublished' => 'Снятие с публикации', 'Permission denied' => 'Доступ запрещён', 'Page activity' => 'Действие со страницей'],
    'statuses' => ['allowed' => 'Разрешено', 'denied' => 'Запрещено', 'draft' => 'Черновик', 'published' => 'Опубликовано'],
    'version' => 'версия :version',
    'not_recorded' => 'Не записано',
];
