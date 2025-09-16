<?php
$groups = json_decode(file_get_contents('config/data/groups.json'), true);
$updated = false;

foreach ($groups as &$group) {
    if (!isset($group['visibility'])) {
        $group['visibility'] = true;
        $updated = true;
    }
}

if ($updated) {
    file_put_contents('config/data/groups.json', json_encode($groups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo 'Visibility field added to all groups';
} else {
    echo 'All groups already have visibility field';
}
?>
