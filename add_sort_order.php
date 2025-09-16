<?php
$groups = json_decode(file_get_contents('config/data/groups.json'), true);
$updated = false;

foreach ($groups as $index => &$group) {
    if (!isset($group['sort_order'])) {
        $group['sort_order'] = $index + 1; // Начинаем с 1
        $updated = true;
    }
}

if ($updated) {
    file_put_contents('config/data/groups.json', json_encode($groups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo 'Sort order field added to all groups';
} else {
    echo 'All groups already have sort_order field';
}
?>
