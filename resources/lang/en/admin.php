<?php

declare(strict_types=1);

return [
    'title' => 'Audit history',
    'eyebrow' => 'Operations',
    'heading' => 'Audit history',
    'description' => 'Review persistent Page activity recorded by Larena. Content bodies and sensitive payload fields are never shown here.',
    'navigation' => ['history' => 'Audit history'],
    'region_label' => 'Page audit history',
    'empty_title' => 'No Page activity yet',
    'empty_description' => 'Create or update a page and its audit history will appear here.',
    'columns' => ['operation' => 'Operation', 'page' => 'Page', 'actor' => 'Actor', 'status' => 'Status', 'time' => 'Time'],
    'operations' => ['Created' => 'Created', 'Updated' => 'Updated', 'Published' => 'Published', 'Unpublished' => 'Unpublished', 'Permission denied' => 'Permission denied', 'Page activity' => 'Page activity'],
    'statuses' => ['allowed' => 'Allowed', 'denied' => 'Denied', 'draft' => 'Draft', 'published' => 'Published'],
    'version' => 'v:version',
    'not_recorded' => 'Not recorded',
];
