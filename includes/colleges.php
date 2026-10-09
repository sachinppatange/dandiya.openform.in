<?php
/**
 * Colleges shown on the registration form. Managed from Admin → Colleges.
 */

function college_seed_rows(): array
{
    return [
        ['Latur College of Pharmacy, Hasegaon', 10, 0],
        ['Latur College of Pharmacy, Latur', 20, 0],
        ['SVSS Latur College of Nursing, Latur', 30, 0],
        ['SVSS Latur College of Physiotherapy, Latur', 40, 0],
        ['Rajiv Gandhi Institute of Polytechnic, Latur', 50, 0],
        ['Latur College of Pvt. ITI, Hasegaon', 60, 0],
        ['Latur College of Science', 70, 0],
        ['Other', 1000, 1],
    ];
}

function ensure_colleges_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        if (!function_exists('db_ensure_column')) {
            require_once __DIR__ . '/../db.php';
        }
        $pdo = db();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `colleges` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(200) NOT NULL,
              `sort_order` int(11) NOT NULL DEFAULT 0,
              `is_other` tinyint(1) NOT NULL DEFAULT 0,
              `status` enum('active','inactive') NOT NULL DEFAULT 'active',
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        if (function_exists('db_ensure_column')) {
            db_ensure_column($pdo, 'scholarship_applications', 'college_id', 'int(11) DEFAULT NULL');
        }
        $count = (int) $pdo->query('SELECT COUNT(*) FROM colleges')->fetchColumn();
        if ($count === 0) {
            $stmt = $pdo->prepare('INSERT INTO colleges (name, sort_order, is_other, status) VALUES (?, ?, ?, \'active\')');
            foreach (college_seed_rows() as $row) {
                $stmt->execute($row);
            }
        }
    } catch (Throwable $e) {
        error_log('colleges schema: ' . $e->getMessage());
    }
}

function college_list(bool $activeOnly = false): array
{
    ensure_colleges_schema();
    try {
        $sql = 'SELECT * FROM colleges';
        if ($activeOnly) {
            $sql .= " WHERE status = 'active'";
        }
        $sql .= ' ORDER BY is_other ASC, sort_order ASC, name ASC';
        return db()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function college_by_id(int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    ensure_colleges_schema();
    try {
        $stmt = db()->prepare('SELECT * FROM colleges WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function college_form_options(?int $includeId = null): array
{
    $rows = college_list(true);
    if ($includeId && $includeId > 0) {
        $have = false;
        foreach ($rows as $row) {
            if ((int) $row['id'] === $includeId) {
                $have = true;
                break;
            }
        }
        if (!$have) {
            $extra = college_by_id($includeId);
            if ($extra) {
                $rows[] = $extra;
            }
        }
    }
    usort($rows, static function (array $a, array $b): int {
        $ao = (int) ($a['is_other'] ?? 0);
        $bo = (int) ($b['is_other'] ?? 0);
        if ($ao !== $bo) {
            return $ao <=> $bo;
        }
        $as = (int) ($a['sort_order'] ?? 0);
        $bs = (int) ($b['sort_order'] ?? 0);
        if ($as !== $bs) {
            return $as <=> $bs;
        }
        return strcasecmp((string) $a['name'], (string) $b['name']);
    });
    return $rows;
}

function college_match_name(string $name): ?array
{
    $name = trim($name);
    if ($name === '') {
        return null;
    }
    foreach (college_list(false) as $row) {
        if (strcasecmp(trim((string) $row['name']), $name) === 0 && (int) ($row['is_other'] ?? 0) !== 1) {
            return $row;
        }
    }
    return null;
}

function college_apply_post(array $post, int $allowInactiveId = 0): array
{
    $id = (int) ($post['college_id'] ?? 0);
    $other = trim((string) ($post['college_other'] ?? ''));
    $other = preg_replace('/\s+/', ' ', $other) ?? $other;
    $row = college_by_id($id);
    if (!$row) {
        return ['ok' => false, 'error' => 'Please select a college.', 'college_id' => null, 'school_name' => ''];
    }
    $active = ($row['status'] ?? '') === 'active' || $id === $allowInactiveId;
    if (!$active) {
        return ['ok' => false, 'error' => 'Please select a college from the list.', 'college_id' => null, 'school_name' => ''];
    }
    if ((int) ($row['is_other'] ?? 0) === 1) {
        if (strlen($other) < 3) {
            return ['ok' => false, 'error' => 'Please type your college or organisation name.', 'college_id' => $id, 'school_name' => ''];
        }
        if (strlen($other) > 200) {
            return ['ok' => false, 'error' => 'College name must be 200 characters or less.', 'college_id' => $id, 'school_name' => ''];
        }
        return ['ok' => true, 'error' => '', 'college_id' => $id, 'school_name' => $other];
    }
    return ['ok' => true, 'error' => '', 'college_id' => $id, 'school_name' => trim((string) $row['name'])];
}

function college_usage_count(int $id): int
{
    if ($id < 1 || !function_exists('db_table_has_column') || !db_table_has_column(db(), 'scholarship_applications', 'college_id')) {
        return 0;
    }
    try {
        $stmt = db()->prepare('SELECT COUNT(*) FROM scholarship_applications WHERE college_id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function college_save(array $post, int $id = 0): array
{
    ensure_colleges_schema();
    $name = trim((string) ($post['name'] ?? ''));
    $name = preg_replace('/\s+/', ' ', $name) ?? $name;
    $sort = (int) ($post['sort_order'] ?? 0);
    if ($name === '' || strlen($name) < 2 || strlen($name) > 200) {
        return ['ok' => false, 'error' => 'Enter a college name (2–200 characters).'];
    }
    $existing = $id > 0 ? college_by_id($id) : null;
    if ($id > 0 && !$existing) {
        return ['ok' => false, 'error' => 'College not found.'];
    }
    try {
        $dup = db()->prepare('SELECT id FROM colleges WHERE name = ? AND id <> ? LIMIT 1');
        $dup->execute([$name, $id]);
        if ($dup->fetchColumn()) {
            return ['ok' => false, 'error' => 'This college is already in the list.'];
        }
        if ($existing) {
            $stmt = db()->prepare('UPDATE colleges SET name = ?, sort_order = ? WHERE id = ?');
            $stmt->execute([$name, $sort, $id]);
            if ((int) ($existing['is_other'] ?? 0) !== 1 && $name !== (string) $existing['name'] && function_exists('db_table_has_column') && db_table_has_column(db(), 'scholarship_applications', 'college_id')) {
                $up = db()->prepare('UPDATE scholarship_applications SET school_name = ? WHERE college_id = ?');
                $up->execute([$name, $id]);
            }
            return ['ok' => true, 'error' => ''];
        }
        $stmt = db()->prepare("INSERT INTO colleges (name, sort_order, is_other, status) VALUES (?, ?, 0, 'active')");
        $stmt->execute([$name, $sort]);
        return ['ok' => true, 'error' => ''];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Could not save the college.'];
    }
}

function college_set_status(int $id, string $status): array
{
    $row = college_by_id($id);
    if (!$row) {
        return ['ok' => false, 'error' => 'College not found.'];
    }
    if ((int) ($row['is_other'] ?? 0) === 1) {
        return ['ok' => false, 'error' => 'Other stays on the form so a guest can type a college that is not listed.'];
    }
    $status = $status === 'inactive' ? 'inactive' : 'active';
    try {
        $stmt = db()->prepare('UPDATE colleges SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        return ['ok' => true, 'error' => ''];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Could not update status.'];
    }
}

function college_delete(int $id): array
{
    $row = college_by_id($id);
    if (!$row) {
        return ['ok' => false, 'error' => 'College not found.'];
    }
    if ((int) ($row['is_other'] ?? 0) === 1) {
        return ['ok' => false, 'error' => 'Other stays on the form so a guest can type a college that is not listed.'];
    }
    $used = college_usage_count($id);
    if ($used > 0) {
        return ['ok' => false, 'error' => 'This college is on ' . $used . ' registration' . ($used === 1 ? '' : 's') . '. Hide it instead of deleting it.'];
    }
    try {
        $stmt = db()->prepare('DELETE FROM colleges WHERE id = ? AND is_other = 0');
        $stmt->execute([$id]);
        return ['ok' => true, 'error' => ''];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Could not delete the college.'];
    }
}

function college_field(int $selectedId, string $otherName, string $currentSchool = ''): void
{
    ensure_colleges_schema();
    if ($selectedId < 1 && $currentSchool !== '') {
        $match = college_match_name($currentSchool);
        if ($match) {
            $selectedId = (int) $match['id'];
        } else {
            foreach (college_list(false) as $row) {
                if ((int) ($row['is_other'] ?? 0) === 1) {
                    $selectedId = (int) $row['id'];
                    if ($otherName === '') {
                        $otherName = $currentSchool;
                    }
                    break;
                }
            }
        }
    }
    $options = college_form_options($selectedId > 0 ? $selectedId : null);
    $selected = null;
    foreach ($options as $row) {
        if ((int) $row['id'] === $selectedId) {
            $selected = $row;
            break;
        }
    }
    $showOther = $selected && (int) ($selected['is_other'] ?? 0) === 1;
    ?>
    <label class="form-label" for="collegeSelect">College / organisation <span class="text-danger required">*</span></label>
    <input type="hidden" name="institution_type" value="academia">
    <select name="college_id" id="collegeSelect" class="form-select" required>
        <option value="">-- Select college --</option>
        <?php foreach ($options as $row): ?>
        <option value="<?php echo (int) $row['id']; ?>" data-other="<?php echo (int) ($row['is_other'] ?? 0) === 1 ? '1' : '0'; ?>" <?php echo $selectedId === (int) $row['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $row['name']); ?><?php echo ($row['status'] ?? '') !== 'active' ? ' (hidden)' : ''; ?></option>
        <?php endforeach; ?>
    </select>
    <div id="collegeOtherWrap" class="mt-2" style="<?php echo $showOther ? '' : 'display:none;'; ?>">
        <label class="form-label" for="collegeOther">Your college / organisation <span class="text-danger required">*</span></label>
        <input type="text" name="college_other" id="collegeOther" class="form-control" maxlength="200" placeholder="Type the college or organisation name" value="<?php echo htmlspecialchars($otherName); ?>" <?php echo $showOther ? 'required minlength="3"' : ''; ?>>
    </div>
    <script>
    (function () {
        var sel = document.getElementById('collegeSelect');
        var wrap = document.getElementById('collegeOtherWrap');
        var input = document.getElementById('collegeOther');
        if (!sel || !wrap || !input) return;
        function sync() {
            var opt = sel.options[sel.selectedIndex];
            var other = !!(opt && opt.getAttribute('data-other') === '1');
            wrap.style.display = other ? '' : 'none';
            input.required = other;
            if (other) input.setAttribute('minlength', '3');
            else input.removeAttribute('minlength');
        }
        sel.addEventListener('change', sync);
        sync();
    })();
    </script>
    <?php
}
