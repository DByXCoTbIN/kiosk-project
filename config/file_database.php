<?php
class FileDatabase {
    private $dataDir;
    
    public function __construct() {
        $this->dataDir = __DIR__ . '/data/';
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0777, true);
        }
    }
    
    // Метод для проверки админских учетных данных
    public function checkAdminCredentials($username, $password) {
        // Простая проверка для демо (в реальном проекте используйте хеширование паролей)
        $adminUsers = [
            'admin' => 'admin123',
            'administrator' => 'password123'
        ];
        
        return isset($adminUsers[$username]) && $adminUsers[$username] === $password;
    }
    
    // --- Groups ---
    private function getGroupsFile() { return $this->dataDir . 'groups.json'; }

    public function getAllGroups() {
        $file = $this->getGroupsFile();
        return $this->loadData($file);
    }

    public function addGroup($groupData) {
        $groups = $this->getAllGroups();
        
        // Генерируем новый ID
        $newId = 1;
        if (!empty($groups)) {
            $maxId = max(array_column($groups, 'id'));
            $newId = $maxId + 1;
        }
        
        $groupData['id'] = $newId;
        $groupData['created_at'] = date('Y-m-d H:i:s');
        $groups[] = $groupData;
        $this->saveData($this->getGroupsFile(), $groups);
        return $groupData['id'];
    }

    public function getGroupById($id) {
        $groups = $this->getAllGroups();
        foreach ($groups as $group) {
            if ($group['id'] == $id) {
                return $group;
            }
        }
        return null;
    }

    public function updateGroup($id, $data) {
        $groups = $this->getAllGroups();
        foreach ($groups as &$group) {
            if ($group['id'] == $id) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                $group = array_merge($group, $data);
                $this->saveData($this->getGroupsFile(), $groups);
                return true;
            }
        }
        return false;
    }

    public function getTeachersByGroupId($groupId) {
        $group = $this->getGroupById($groupId);
        if (!$group || !isset($group['teacher_ids']) || empty($group['teacher_ids'])) {
            return [];
        }

        $teachers = $this->getAllTeachers();
        $groupTeachers = [];

        foreach ($teachers as $teacher) {
            if (in_array($teacher['id'], $group['teacher_ids'])) {
                $groupTeachers[] = $teacher;
            }
        }

        return $groupTeachers;
    }

    // --- Teachers ---
    private function getTeachersFile() { return $this->dataDir . 'teachers.json'; }

    public function getAllTeachers() {
        $file = $this->getTeachersFile();
        return $this->loadData($file);
    }

    public function addTeacher($data) {
        $teachers = $this->getAllTeachers();
        $data['id'] = $this->generateId($teachers);
        $data['created_at'] = date('Y-m-d H:i:s');
        $teachers[] = $data;
        $this->saveData($this->getTeachersFile(), $teachers);
        return $data['id'];
    }

    public function getTeacherById($id) {
        $teachers = $this->getAllTeachers();
        foreach ($teachers as $teacher) {
            if ($teacher['id'] == $id) {
                return $teacher;
            }
        }
        return null;
    }

    public function updateTeacher($id, $data) {
        $teachers = $this->getAllTeachers();
        foreach ($teachers as &$teacher) {
            if ($teacher['id'] == $id) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                $teacher = array_merge($teacher, $data);
                $this->saveData($this->getTeachersFile(), $teachers);
                return true;
            }
        }
        return false;
    }

    // public function deleteTeacher($id) {
    //     $teachers = $this->getAllTeachers();
    //     $newTeachers = [];
    //     foreach ($teachers as $teacher) {
    //         if ($teacher['id'] != $id) {
    //             $newTeachers[] = $teacher;
    //         }
    //     }
    //     $this->saveData($this->getTeachersFile(), $newTeachers);
    //     return count($teachers) > count($newTeachers);
    // }

    public function deleteTeacher($id) {
    try {
        $dataFile = $this->dataDir . '/teachers.json';
        
        // Проверяем, существует ли файл
        if (!file_exists($dataFile)) {
            error_log("Teachers file does not exist: " . $dataFile);
            return false;
        }
        
        // Читаем текущие данные
        $teachers = $this->getAllTeachers();
        if (empty($teachers)) {
            error_log("No teachers found or empty array");
            return false;
        }
        
        // Ищем педагога с указанным ID
        $found = false;
        $filteredTeachers = [];
        
        foreach ($teachers as $teacher) {
            if (isset($teacher['id']) && $teacher['id'] == $id) {
                $found = true;
                error_log("Found teacher to delete: " . $teacher['full_name']);
                // Не добавляем этого педагога в отфильтрованный массив
            } else {
                $filteredTeachers[] = $teacher;
            }
        }
        
        if (!$found) {
            error_log("Teacher with ID $id not found");
            return false;
        }
        
        // Сохраняем обновленные данные
        $jsonData = json_encode($filteredTeachers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $result = file_put_contents($dataFile, $jsonData);
        
        if ($result === false) {
            error_log("Failed to write to file: " . $dataFile);
            return false;
        }
        
        error_log("Successfully deleted teacher with ID: " . $id);
        return true;
        
    } catch (Exception $e) {
        error_log("Exception in deleteTeacher: " . $e->getMessage());
        return false;
    }
}

public function deleteGroup($id) {
    try {
        $dataFile = $this->dataDir . '/groups.json';
        
        // Проверяем, существует ли файл
        if (!file_exists($dataFile)) {
            error_log("Groups file does not exist: " . $dataFile);
            return false;
        }
        
        // Читаем текущие данные
        $groups = $this->getAllGroups();
        if (empty($groups)) {
            error_log("No groups found or empty array");
            return false;
        }
        
        // Ищем группу с указанным ID
        $found = false;
        $filteredGroups = [];
        
        foreach ($groups as $group) {
            if (isset($group['id']) && $group['id'] == $id) {
                $found = true;
                error_log("Found group to delete: " . $group['name']);
                // Не добавляем эту группу в отфильтрованный массив
            } else {
                $filteredGroups[] = $group;
            }
        }
        
        if (!$found) {
            error_log("Group with ID $id not found");
            return false;
        }
        
        // Сохраняем обновленные данные
        $jsonData = json_encode($filteredGroups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $result = file_put_contents($dataFile, $jsonData);
        
        if ($result === false) {
            error_log("Failed to write to file: " . $dataFile);
            return false;
        }
        
        error_log("Successfully deleted group with ID: " . $id);
        return true;
        
    } catch (Exception $e) {
        error_log("Exception in deleteGroup: " . $e->getMessage());
        return false;
    }
}
    // --- Schedule ---
    private function getScheduleFile() { return $this->dataDir . 'schedule.json'; }

    public function getAllSchedule() {
        $file = $this->getScheduleFile();
        return $this->loadData($file);
    }

    public function addSchedule($data) {
        $schedules = $this->getAllSchedule();
        $data['id'] = $this->generateId($schedules);
        $data['created_at'] = date('Y-m-d H:i:s');
        $schedules[] = $data;
        $this->saveData($this->getScheduleFile(), $schedules);
        return $data['id'];
    }

    public function getScheduleById($id) {
        $schedules = $this->getAllSchedule();
        foreach ($schedules as $schedule) {
            if ($schedule['id'] == $id) {
                return $schedule;
            }
        }
        return null;
    }

    public function updateSchedule($id, $data) {
        $schedules = $this->getAllSchedule();
        foreach ($schedules as &$schedule) {
            if ($schedule['id'] == $id) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                $schedule = array_merge($schedule, $data);
                $this->saveData($this->getScheduleFile(), $schedules);
                return true;
            }
        }
        return false;
    }

    public function deleteSchedule($id) {
        $schedules = $this->getAllSchedule();
        $newSchedules = [];
        foreach ($schedules as $schedule) {
            if ($schedule['id'] != $id) {
                $newSchedules[] = $schedule;
            }
        }
        $this->saveData($this->getScheduleFile(), $newSchedules);
        return count($schedules) > count($newSchedules);
    }

    // --- Carousel ---
    private function getCarouselFile() { return $this->dataDir . 'carousel.json'; }

    public function getAllCarouselItems() {
        $file = $this->getCarouselFile();
        return $this->loadData($file);
    }

    public function addCarouselItem($data) {
        $items = $this->getAllCarouselItems();
        $data['id'] = $this->generateId($items);
        $data['created_at'] = date('Y-m-d H:i:s');
        $items[] = $data;
        $this->saveData($this->getCarouselFile(), $items);
        return $data['id'];
    }

    public function getCarouselItemById($id) {
        $items = $this->getAllCarouselItems();
        foreach ($items as $item) {
            if ($item['id'] == $id) {
                return $item;
            }
        }
        return null;
    }

    public function updateCarouselItem($id, $data) {
        $items = $this->getAllCarouselItems();
        foreach ($items as &$item) {
            if ($item['id'] == $id) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                $item = array_merge($item, $data);
                $this->saveData($this->getCarouselFile(), $items);
                return true;
            }
        }
        return false;
    }

    public function deleteCarouselItem($id) {
        $items = $this->getAllCarouselItems();
        $newItems = [];
        foreach ($items as $item) {
            if ($item['id'] != $id) {
                $newItems[] = $item;
            }
        }
        $this->saveData($this->getCarouselFile(), $newItems);
        return count($items) > count($newItems);
    }

    // --- Utility functions ---
    private function loadData($file) {
        if (!file_exists($file)) {
            return [];
        }
        $data = file_get_contents($file);
        $decoded = json_decode($data, true);
        if ($decoded === null) {
            return [];
        }
        // Stripslashes for all string values to handle escaped paths
        array_walk_recursive($decoded, function(&$value) {
            if (is_string($value)) {
                $value = stripslashes($value);
            }
        });
        return $decoded;
    }

    private function saveData($file, $data) {
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function generateId($data) {
        $maxId = 0;
        foreach ($data as $item) {
            if (isset($item['id']) && $item['id'] > $maxId) {
                $maxId = $item['id'];
            }
        }
        return $maxId + 1;
    }
}
?>
