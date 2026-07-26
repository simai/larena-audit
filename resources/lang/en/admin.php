<?php

declare(strict_types=1);

return [
    'title' => 'Audit history',
    'eyebrow' => 'Operations',
    'heading' => 'Audit history',
    'description' => 'Review persistent security and content activity. Sensitive payload fields are never shown here.',
    'navigation' => ['history' => 'Audit history'],
    'region_label' => 'Audit history',
    'empty_title' => 'No Page activity yet',
    'empty_description' => 'Create or update a page and its audit history will appear here. Access activity will appear here too.',
    'columns' => ['operation' => 'Operation', 'subject' => 'Subject', 'actor' => 'Actor', 'detail' => 'Safe detail', 'time' => 'Time'],
    'operations' => ['Created' => 'Created', 'Updated' => 'Updated', 'Published' => 'Published', 'Unpublished' => 'Unpublished', 'Submitted for publication' => 'Submitted for publication', 'Restored' => 'Restored', 'Permission denied' => 'Permission denied', 'File uploaded'=>'File uploaded','File metadata updated'=>'File metadata updated','File deleted'=>'File deleted','File used on page'=>'File used on page','Page activity' => 'Page activity', 'Security activity' => 'Security activity'],
    'statuses' => ['allowed' => 'Allowed', 'denied' => 'Denied', 'draft' => 'Draft', 'published' => 'Published'],
    'version' => 'v:version',
    'not_recorded' => 'Not recorded',
];
