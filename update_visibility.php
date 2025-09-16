<?php
$groups = json_decode(file_get_contents('config/data/groups.json'), true);
foreach ($groups as &$group) {
    if (!isset($group['visibility'])) {
        $group['visibility'] = true;
    }
}
file_put_contents('config/data/groups.json', json_encode($groups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'Updated all groups with visibility field';
?>
