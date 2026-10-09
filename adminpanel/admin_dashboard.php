<?php
session_start();
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/wa_config.php';
require_once __DIR__ . '/../includes/panel_layout.php';
if (!function_exists('form_all_class_labels')) {
    require_once __DIR__ . '/../includes/form_catalog.php';
}
require_once __DIR__ . '/registration_query.php';

if (empty($_SESSION['admin_auth_user'])) {
    header('Location: admin_login.php?next=admin_dashboard.php');
    exit;
}
if (function_exists('ensure_form_catalog_schema')) {
    ensure_form_catalog_schema();
}

$tab = $_GET['tab'] ?? 'analytics';
if (!in_array($tab, ['analytics', 'list'], true)) {
    $tab = 'analytics';
}

$f = registration_read_filters($_GET);
$class_labels = registration_class_labels();
$current_filter = $f['status'];
$classFilter = $f['class'];
if ($classFilter !== '' && !isset($class_labels[$classFilter])) {
    $classFilter = '';
    $f['class'] = '';
}
$instFilter = $f['inst'];
$sizeFilter = $f['size'];
$eventFilter = $f['event'];
$q = $f['q'];
$range = $f['range'];

$rangeSql = '';
if ($range === '7') $rangeSql = 'created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
elseif ($range === '30') $rangeSql = 'created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
$RW = $rangeSql !== '' ? "WHERE $rangeSql" : '';
$rangeSqlA = $rangeSql !== '' ? str_replace('created_at', 'a.created_at', $rangeSql) : '';
$RWA = $rangeSqlA !== '' ? "WHERE $rangeSqlA" : '';

$pct = static function ($part, $total): float {
    $total = (float) $total;
    return $total > 0 ? round(((float) $part / $total) * 100, 1) : 0.0;
};
$fillDays = static function (array $rows, int $days, array $keys) {
    $by = [];
    foreach ($rows as $row) {
        $by[(string) $row['d']] = $row;
    }
    $out = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $src = $by[$d] ?? [];
        $item = ['d' => $d, 'label' => date('d M', strtotime($d))];
        foreach ($keys as $key) {
            $item[$key] = (float) ($src[$key] ?? 0);
        }
        $out[] = $item;
    }
    return $out;
};

$counts = ['total'=>0,'paid_count'=>0,'pending_count'=>0,'failed_count'=>0,'total_revenue'=>0,'pending_amt'=>0,'failed_amt'=>0];
$today_stats = ['today_applications'=>0,'today_paid'=>0,'today_revenue'=>0];
$class_stats = [];
$staffCount = 0;
$success_rate = 0;
$sparkDays = [];
$aging = [
    'today' => ['cnt'=>0,'amt'=>0],
    'd7' => ['cnt'=>0,'amt'=>0],
    'd30' => ['cnt'=>0,'amt'=>0],
    'old' => ['cnt'=>0,'amt'=>0],
];
$board_stats = [];
$medium_stats = [];
$inst_stats = [];
$event_stats = [];
$districts = [];
$schools = [];
$topStaff = [];
$monthly = [];
$trendDaysData = [];
$pendingRows = [];

try {
    $pdo = db();

    $counts = $pdo->query("
        SELECT COUNT(*) total,
               COALESCE(SUM(payment_status='paid'),0) paid_count,
               COALESCE(SUM(payment_status='pending'),0) pending_count,
               COALESCE(SUM(payment_status='failed'),0) failed_count,
               COALESCE(SUM(CASE WHEN payment_status='paid' THEN exam_fee ELSE 0 END),0) total_revenue,
               COALESCE(SUM(CASE WHEN payment_status='pending' THEN exam_fee ELSE 0 END),0) pending_amt,
               COALESCE(SUM(CASE WHEN payment_status='failed' THEN exam_fee ELSE 0 END),0) failed_amt
          FROM scholarship_applications
          $RW
    ")->fetch(PDO::FETCH_ASSOC) ?: $counts;

    $today_stats = $pdo->query("
        SELECT COUNT(*) today_applications,
               COALESCE(SUM(payment_status='paid'),0) today_paid,
               COALESCE(SUM(CASE WHEN payment_status='paid' THEN exam_fee ELSE 0 END),0) today_revenue
          FROM scholarship_applications
         WHERE DATE(created_at) = CURDATE()
    ")->fetch(PDO::FETCH_ASSOC) ?: $today_stats;

    $class_stats = $pdo->query("
        SELECT class,
               COUNT(*) cnt,
               COALESCE(SUM(payment_status='paid'),0) paid_count,
               COALESCE(SUM(payment_status='pending'),0) pending_count,
               COALESCE(SUM(CASE WHEN payment_status='paid' THEN exam_fee ELSE 0 END),0) paid_amt
          FROM scholarship_applications
          $RW
         GROUP BY class
         ORDER BY cnt DESC, class ASC
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

    try { $staffCount = (int) $pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn(); } catch (Throwable $e) {}

    $success_rate = $pct($counts['paid_count'], $counts['total']);

    $sparkRaw = $pdo->query("
        SELECT DATE(created_at) d,
               COUNT(*) total,
               COALESCE(SUM(payment_status='paid'),0) paid,
               COALESCE(SUM(payment_status='pending'),0) pending,
               COALESCE(SUM(CASE WHEN payment_status='paid' THEN exam_fee ELSE 0 END),0) collected
          FROM scholarship_applications
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(created_at)
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $sparkDays = $fillDays($sparkRaw, 7, ['total','paid','pending','collected']);

    $agingRow = $pdo->query("
        SELECT
          COALESCE(SUM(DATEDIFF(CURDATE(), DATE(created_at)) <= 0),0) today_cnt,
          COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(created_at)) <= 0 THEN exam_fee ELSE 0 END),0) today_amt,
          COALESCE(SUM(DATEDIFF(CURDATE(), DATE(created_at)) BETWEEN 1 AND 7),0) d7_cnt,
          COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(created_at)) BETWEEN 1 AND 7 THEN exam_fee ELSE 0 END),0) d7_amt,
          COALESCE(SUM(DATEDIFF(CURDATE(), DATE(created_at)) BETWEEN 8 AND 30),0) d30_cnt,
          COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(created_at)) BETWEEN 8 AND 30 THEN exam_fee ELSE 0 END),0) d30_amt,
          COALESCE(SUM(DATEDIFF(CURDATE(), DATE(created_at)) > 30),0) old_cnt,
          COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(created_at)) > 30 THEN exam_fee ELSE 0 END),0) old_amt
          FROM scholarship_applications
         WHERE payment_status = 'pending'
    ")->fetch(PDO::FETCH_ASSOC) ?: [];
    $aging = [
        'today' => ['cnt'=>(int)($agingRow['today_cnt']??0),'amt'=>(float)($agingRow['today_amt']??0)],
        'd7'    => ['cnt'=>(int)($agingRow['d7_cnt']??0),'amt'=>(float)($agingRow['d7_amt']??0)],
        'd30'   => ['cnt'=>(int)($agingRow['d30_cnt']??0),'amt'=>(float)($agingRow['d30_amt']??0)],
        'old'   => ['cnt'=>(int)($agingRow['old_cnt']??0),'amt'=>(float)($agingRow['old_amt']??0)],
    ];

    $inst_stats = $pdo->query("
        SELECT IFNULL(NULLIF(TRIM(institution_type),''), 'unknown') label, COUNT(*) cnt,
               COALESCE(SUM(payment_status='paid'),0) paid
          FROM scholarship_applications $RW
         GROUP BY label ORDER BY cnt DESC LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($inst_stats as &$is) {
        if ($is['label'] === 'academia') $is['label'] = 'Academia';
        elseif ($is['label'] === 'industry') $is['label'] = 'Industry';
        elseif ($is['label'] === 'other') $is['label'] = 'Other';
        elseif ($is['label'] === 'school') $is['label'] = 'School';
        elseif ($is['label'] === 'college') $is['label'] = 'College';
        elseif ($is['label'] === 'unknown') $is['label'] = 'Not set';
    }
    unset($is);

    $districts = $pdo->query("
        SELECT IFNULL(NULLIF(TRIM(school_name),''), 'Unknown') label, COUNT(*) cnt,
               COALESCE(SUM(payment_status='paid'),0) paid,
               COALESCE(SUM(CASE WHEN payment_status='paid' THEN exam_fee ELSE 0 END),0) amt
          FROM scholarship_applications $RW
         GROUP BY label ORDER BY cnt DESC LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $schools = $pdo->query("
        SELECT IFNULL(NULLIF(TRIM(school_name),''), 'Unknown') label, COUNT(*) cnt,
               COALESCE(SUM(payment_status='paid'),0) paid
          FROM scholarship_applications $RW
         GROUP BY label ORDER BY cnt DESC LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

    try {
        $hasStaffCol = $pdo->query("SHOW COLUMNS FROM scholarship_applications LIKE 'submitted_by_staff_id'")->fetch();
        if ($hasStaffCol) {
            $topStaff = $pdo->query("
                SELECT COALESCE(NULLIF(TRIM(s.name),''), 'Direct / participant') label,
                       COUNT(*) cnt,
                       COALESCE(SUM(a.payment_status='paid'),0) paid,
                       COALESCE(SUM(CASE WHEN a.payment_status='paid' THEN a.exam_fee ELSE 0 END),0) amt
                  FROM scholarship_applications a
             LEFT JOIN staff s ON s.id = a.submitted_by_staff_id
                  $RWA
              GROUP BY a.submitted_by_staff_id, s.name
              ORDER BY cnt DESC
                 LIMIT 8
            ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (Throwable $e) {
        $topStaff = [];
    }

    $monthMap = [];
    for ($i = 5; $i >= 0; $i--) {
        $key = date('Y-m', strtotime("first day of -{$i} month"));
        $monthMap[$key] = [
            'label' => date('M Y', strtotime($key . '-01')),
            'cnt' => 0, 'paid' => 0, 'amt' => 0, 'paid_amt' => 0,
        ];
    }
    $monthRows = $pdo->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') ym,
               COUNT(*) cnt,
               COALESCE(SUM(payment_status='paid'),0) paid,
               COALESCE(SUM(exam_fee),0) amt,
               COALESCE(SUM(CASE WHEN payment_status='paid' THEN exam_fee ELSE 0 END),0) paid_amt
          FROM scholarship_applications
         WHERE created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
         GROUP BY ym ORDER BY ym
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($monthRows as $row) {
        if (isset($monthMap[$row['ym']])) {
            $monthMap[$row['ym']]['cnt'] = (int) $row['cnt'];
            $monthMap[$row['ym']]['paid'] = (int) $row['paid'];
            $monthMap[$row['ym']]['amt'] = (float) $row['amt'];
            $monthMap[$row['ym']]['paid_amt'] = (float) $row['paid_amt'];
        }
    }
    $monthly = array_values($monthMap);

    $trendLen = $range === '7' ? 7 : 14;
    $trendRaw = $pdo->query("
        SELECT DATE(created_at) d,
               COUNT(*) total,
               COALESCE(SUM(payment_status='paid'),0) paid,
               COALESCE(SUM(exam_fee),0) amt,
               COALESCE(SUM(CASE WHEN payment_status='paid' THEN exam_fee ELSE 0 END),0) paid_amt
          FROM scholarship_applications
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL " . (int) ($trendLen - 1) . " DAY)
         GROUP BY DATE(created_at)
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $trendDaysData = $fillDays($trendRaw, $trendLen, ['total','paid','amt','paid_amt']);

    $pendingRows = $pdo->query("
        SELECT id, first_name, middle_name, last_name, mobile, school_name,
               city, exam_fee, created_at,
               DATEDIFF(CURDATE(), DATE(created_at)) due_days
          FROM scholarship_applications
         WHERE payment_status = 'pending'
         ORDER BY created_at ASC
         LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    // keep defaults
}

if (!$sparkDays) {
    $sparkDays = $fillDays([], 7, ['total','paid','pending','collected']);
}
if (!$monthly) {
    for ($i = 5; $i >= 0; $i--) {
        $key = date('Y-m', strtotime("first day of -{$i} month"));
        $monthly[] = [
            'label' => date('M Y', strtotime($key . '-01')),
            'cnt' => 0, 'paid' => 0, 'amt' => 0, 'paid_amt' => 0,
        ];
    }
}
$trendLen = $range === '7' ? 7 : 14;
if (!$trendDaysData) {
    $trendDaysData = $fillDays([], $trendLen, ['total','paid','amt','paid_amt']);
}

$total = (int) $counts['total'];
$paid = (int) $counts['paid_count'];
$pending = (int) $counts['pending_count'];
$failed = (int) $counts['failed_count'];
$collected = (float) $counts['total_revenue'];
$pendingAmt = (float) $counts['pending_amt'];
$agingTotalCnt = $aging['today']['cnt'] + $aging['d7']['cnt'] + $aging['d30']['cnt'] + $aging['old']['cnt'];
$agingTotalAmt = $aging['today']['amt'] + $aging['d7']['amt'] + $aging['d30']['amt'] + $aging['old']['amt'];
$agingSegs = [
    ['key'=>'today','label'=>'Today','cls'=>'age-now','cnt'=>$aging['today']['cnt'],'amt'=>$aging['today']['amt']],
    ['key'=>'d7','label'=>'1–7 days','cls'=>'age-d7','cnt'=>$aging['d7']['cnt'],'amt'=>$aging['d7']['amt']],
    ['key'=>'d30','label'=>'8–30 days','cls'=>'age-d30','cnt'=>$aging['d30']['cnt'],'amt'=>$aging['d30']['amt']],
    ['key'=>'old','label'=>'30+ days','cls'=>'age-old','cnt'=>$aging['old']['cnt'],'amt'=>$aging['old']['amt']],
];

$qs = static function (array $extra = []) use ($current_filter, $classFilter, $q, $range, $instFilter, $sizeFilter, $eventFilter, $tab): string {
    return '?' . http_build_query(array_merge([
        'tab' => $tab,
        'filter' => $current_filter,
        'class' => $classFilter,
        'inst' => $instFilter,
        'size' => $sizeFilter,
        'event' => $eventFilter,
        'q' => $q,
        'range' => $range,
    ], $extra));
};
$sparkMax = static function (array $days, string $key): float {
    $max = 0;
    foreach ($days as $d) $max = max($max, (float) $d[$key]);
    return $max > 0 ? $max : 1;
};
$exportQs = http_build_query([
    'filter' => $current_filter,
    'class' => $classFilter,
    'inst' => $instFilter,
    'size' => $sizeFilter,
    'event' => $eventFilter,
    'q' => $q,
    'range' => $range,
]);
$rangeLabel = $range === '7' ? 'Last 7 days' : ($range === '30' ? 'Last 30 days' : 'All time');
$districtMax = 0;
foreach ($districts as $d) $districtMax = max($districtMax, (int) $d['cnt']);
$districtMax = $districtMax ?: 1;

$event_stats = [];
foreach ($class_stats as $cs) {
    $event_stats[] = [
        'label' => $class_labels[$cs['class'] ?? ''] ?? (string) ($cs['class'] ?? ''),
        'cnt' => $cs['cnt'] ?? 0,
        'paid' => $cs['paid_count'] ?? 0,
    ];
}

$chartPayload = [
    'status' => [$paid, $pending, $failed],
    'classLabels' => array_map(fn($r) => $class_labels[$r['class']] ?? $r['class'], $class_stats),
    'classTotal' => array_map(fn($r) => (int) $r['cnt'], $class_stats),
    'classPaid' => array_map(fn($r) => (int) $r['paid_count'], $class_stats),
    'trendLabels' => array_map(fn($r) => $r['label'], $trendDaysData),
    'trendTotal' => array_map(fn($r) => (int) $r['total'], $trendDaysData),
    'trendPaid' => array_map(fn($r) => (int) $r['paid'], $trendDaysData),
    'monthLabels' => array_map(fn($r) => $r['label'], $monthly),
    'monthCnt' => array_map(fn($r) => (int) $r['cnt'], $monthly),
    'monthPaid' => array_map(fn($r) => (int) $r['paid'], $monthly),
    'monthAmt' => array_map(fn($r) => (float) $r['amt'], $monthly),
    'monthPaidAmt' => array_map(fn($r) => (float) $r['paid_amt'], $monthly),
    'boardLabels' => array_map(fn($r) => $r['label'], $inst_stats),
    'boardCnt' => array_map(fn($r) => (int) $r['cnt'], $inst_stats),
    'mediumLabels' => array_map(fn($r) => $r['label'], $event_stats),
    'mediumCnt' => array_map(fn($r) => (int) $r['cnt'], $event_stats),
];

panel_start([
    'title' => $tab === 'list' ? 'Registration' : 'Analytics',
    'role' => 'admin',
    'active' => $tab === 'list' ? 'registration' : 'dashboard',
    'name' => get_admin_name(),
    'phone' => local_phone_display($_SESSION['admin_auth_user'] ?? ''),
    'asset_prefix' => '../',
    'links' => admin_nav_links(),
    'head' => '<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">',
]);
$adminName = get_admin_name();
$analyticsQs = $qs(['tab' => 'analytics']);
$listQs = $qs(['tab' => 'list']);
?>
<div class="dash-page">
  <div class="settings-tabs" style="margin-bottom:14px;">
    <a class="<?php echo $tab === 'analytics' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($analyticsQs); ?>">Analytics</a>
    <a class="<?php echo $tab === 'list' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($listQs); ?>">Registration</a>
  </div>
  <div class="dash-toolbar">
    <div>
      <div class="dash-kicker">Admin panel</div>
      <h2>Welcome, <?php echo htmlspecialchars($adminName); ?></h2>
      <p><?php echo (int) $today_stats['today_applications']; ?> registrations today · <?php echo (int) $today_stats['today_paid']; ?> paid · ₹<?php echo number_format((float) $today_stats['today_revenue'], 0); ?> collected today</p>
    </div>
    <form class="dash-toolbar-actions" method="get">
      <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
      <input type="hidden" name="filter" value="<?php echo htmlspecialchars($current_filter); ?>">
      <input type="hidden" name="class" value="<?php echo htmlspecialchars($classFilter); ?>">
      <input type="hidden" name="inst" value="<?php echo htmlspecialchars($instFilter); ?>">
      <input type="hidden" name="size" value="<?php echo htmlspecialchars($sizeFilter); ?>">
      <input type="hidden" name="event" value="<?php echo htmlspecialchars($eventFilter); ?>">
      <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>">
      <select name="range" onchange="this.form.submit()">
        <option value="all" <?php echo $range==='all'?'selected':''; ?>>All time</option>
        <option value="7" <?php echo $range==='7'?'selected':''; ?>>Last 7 days</option>
        <option value="30" <?php echo $range==='30'?'selected':''; ?>>Last 30 days</option>
      </select>
      <a class="btn sm gray" href="<?php echo htmlspecialchars($qs()); ?>">Refresh</a>
      <a class="btn sm" href="admin_export.php?format=excel&amp;<?php echo htmlspecialchars($exportQs); ?>">Excel</a>
      <a class="btn sm gold" href="admin_export.php?format=pdf&amp;<?php echo htmlspecialchars($exportQs); ?>" target="_blank">PDF</a>
      <a class="btn sm gray" href="admin_export.php?format=csv&amp;<?php echo htmlspecialchars($exportQs); ?>">CSV</a>
    </form>
  </div>

  <div class="dash-kpis">
    <a class="kpi-card <?php echo $current_filter==='all'?'active':''; ?>" href="<?php echo htmlspecialchars($qs(['tab'=>'list','filter'=>'all'])); ?>">
      <span>Total registrations</span>
      <b><?php echo number_format($total); ?></b>
      <small><?php echo htmlspecialchars($rangeLabel); ?> · <?php echo htmlspecialchars((string) $success_rate); ?>% paid</small>
      <div class="spark" aria-hidden="true">
        <?php $mx = $sparkMax($sparkDays, 'total'); foreach ($sparkDays as $d): ?>
          <i style="height:<?php echo max(12, round(($d['total'] / $mx) * 100)); ?>%"></i>
        <?php endforeach; ?>
      </div>
    </a>
    <a class="kpi-card ok <?php echo $current_filter==='paid'?'active':''; ?>" href="<?php echo htmlspecialchars($qs(['tab'=>'list','filter'=>'paid'])); ?>">
      <span>Paid</span>
      <b><?php echo number_format($paid); ?></b>
      <small><?php echo htmlspecialchars((string) $pct($paid, $total)); ?>% of forms · ₹<?php echo number_format($collected, 0); ?></small>
      <div class="spark ok" aria-hidden="true">
        <?php $mx = $sparkMax($sparkDays, 'paid'); foreach ($sparkDays as $d): ?>
          <i style="height:<?php echo max(12, round(($d['paid'] / $mx) * 100)); ?>%"></i>
        <?php endforeach; ?>
      </div>
    </a>
    <a class="kpi-card warn <?php echo $current_filter==='pending'?'active':''; ?>" href="<?php echo htmlspecialchars($qs(['tab'=>'list','filter'=>'pending'])); ?>">
      <span>Pending</span>
      <b><?php echo number_format($pending); ?></b>
      <small>₹<?php echo number_format($pendingAmt, 0); ?> outstanding</small>
      <div class="spark warn" aria-hidden="true">
        <?php $mx = $sparkMax($sparkDays, 'pending'); foreach ($sparkDays as $d): ?>
          <i style="height:<?php echo max(12, round(($d['pending'] / $mx) * 100)); ?>%"></i>
        <?php endforeach; ?>
      </div>
    </a>
    <a class="kpi-card money" href="<?php echo htmlspecialchars($qs(['tab'=>'list','filter'=>'paid'])); ?>">
      <span>Collected</span>
      <b>₹<?php echo number_format($collected, 0); ?></b>
      <small><?php echo number_format($failed); ?> failed · ₹<?php echo number_format((float)$counts['failed_amt'], 0); ?></small>
      <div class="spark money" aria-hidden="true">
        <?php $mx = $sparkMax($sparkDays, 'collected'); foreach ($sparkDays as $d): ?>
          <i style="height:<?php echo max(12, round(($d['collected'] / $mx) * 100)); ?>%"></i>
        <?php endforeach; ?>
      </div>
    </a>
  </div>

  <?php if ($tab === 'analytics'): ?>
  <div class="dash-row dash-row-2">
    <div class="card">
      <div class="card-head">
        <h3>Paid vs pending</h3>
        <span class="muted"><?php echo htmlspecialchars($rangeLabel); ?></span>
      </div>
      <div class="donut-layout">
        <div class="donut-wrap">
          <canvas id="statusDonut"></canvas>
          <div class="donut-center">
            <b><?php echo htmlspecialchars((string) $success_rate); ?>%</b>
            <small>Paid</small>
          </div>
        </div>
        <ul class="legend-list">
          <li><i class="lg-paid"></i> Paid <b><?php echo number_format($paid); ?></b></li>
          <li><i class="lg-pending"></i> Pending <b><?php echo number_format($pending); ?></b></li>
          <li><i class="lg-failed"></i> Failed <b><?php echo number_format($failed); ?></b></li>
          <li class="legend-meta">Staff logins <b><?php echo number_format($staffCount); ?></b></li>
        </ul>
      </div>
    </div>
    <div class="card">
      <div class="card-head">
        <h3>Pending outstanding</h3>
        <span class="muted"><?php echo number_format($agingTotalCnt); ?> forms · ₹<?php echo number_format($agingTotalAmt, 0); ?></span>
      </div>
      <div class="age-bar" role="img" aria-label="Pending aging">
        <?php foreach ($agingSegs as $seg): $w = $pct($seg['cnt'], $agingTotalCnt); ?>
          <span class="<?php echo $seg['cls']; ?>" style="width:<?php echo max($w > 0 ? 2 : 0, $w); ?>%" title="<?php echo htmlspecialchars($seg['label']); ?>"></span>
        <?php endforeach; ?>
      </div>
      <div class="age-legend">
        <?php foreach ($agingSegs as $seg): ?>
          <div>
            <i class="<?php echo $seg['cls']; ?>"></i>
            <span><?php echo htmlspecialchars($seg['label']); ?></span>
            <b>₹<?php echo number_format($seg['amt'], 0); ?></b>
            <small><?php echo number_format($seg['cnt']); ?> · <?php echo htmlspecialchars((string) $pct($seg['cnt'], $agingTotalCnt)); ?>%</small>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="dash-row dash-row-split">
    <div class="card">
      <div class="card-head">
        <h3>By role</h3>
        <span class="muted"><?php echo htmlspecialchars($rangeLabel); ?></span>
      </div>
      <?php if (!$class_stats): ?>
        <p class="page-sub">No role data yet.</p>
      <?php else: ?>
        <div class="chart-box"><canvas id="classChart"></canvas></div>
      <?php endif; ?>
    </div>
    <div class="card">
      <div class="card-head">
        <h3>By organisation</h3>
        <span class="muted">Top 8</span>
      </div>
      <?php if (!$districts): ?>
        <p class="page-sub">No organisation data yet.</p>
      <?php else: ?>
        <div class="geo-list">
          <?php foreach ($districts as $d): ?>
            <div class="geo-row">
              <div class="geo-top">
                <b><?php echo htmlspecialchars($d['label']); ?></b>
                <span><?php echo (int) $d['cnt']; ?> · <?php echo (int) $d['paid']; ?> paid</span>
              </div>
              <div class="geo-track"><span style="width:<?php echo round(((int)$d['cnt'] / $districtMax) * 100); ?>%"></span></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="dash-row dash-row-2">
    <div class="card">
      <div class="card-head">
        <h3>Daily forms vs paid</h3>
        <span class="muted"><?php echo $range === '7' ? 'Last 7 days' : 'Last 14 days'; ?></span>
      </div>
      <div class="chart-box"><canvas id="trendChart"></canvas></div>
    </div>
    <div class="card">
      <div class="card-head"><h3>Collected vs form amount</h3><span class="muted">Last 6 months</span></div>
      <div class="chart-box"><canvas id="amountChart"></canvas></div>
    </div>
  </div>

  <div class="dash-row dash-row-2">
    <div class="card">
      <div class="card-head"><h3>Role</h3></div>
      <div class="chart-box sm"><canvas id="mediumDonut"></canvas></div>
    </div>
    <div class="card">
      <div class="card-head"><h3>Monthly count</h3></div>
      <div class="chart-box sm"><canvas id="countChart"></canvas></div>
    </div>
  </div>

  <div class="dash-row dash-row-3">
    <?php
    $rankTables = [
        ['title' => 'Top staff', 'rows' => $topStaff, 'value' => 'cnt', 'money' => false],
        ['title' => 'Top organisations', 'rows' => $schools, 'value' => 'cnt', 'money' => false],
        ['title' => 'By role', 'rows' => $event_stats, 'value' => 'cnt', 'money' => false],
    ];
    foreach ($rankTables as $table):
    ?>
    <div class="card rank-card">
      <div class="card-head"><h3><?php echo htmlspecialchars($table['title']); ?></h3></div>
      <?php if (empty($table['rows'])): ?>
        <p class="page-sub">No data yet.</p>
      <?php else: ?>
        <table class="rank-table">
          <thead><tr><th>Name</th><th>Count</th></tr></thead>
          <tbody>
          <?php foreach (array_slice($table['rows'], 0, 6) as $row): ?>
            <tr>
              <td><?php echo htmlspecialchars((string) $row['label']); ?></td>
              <td>
                <?php echo number_format((int)$row[$table['value']]); ?>
                <small><?php echo (int) $row['paid']; ?> paid</small>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-head">
      <h3>Pending payments</h3>
      <a class="view-all" href="<?php echo htmlspecialchars($qs(['tab'=>'list','filter'=>'pending'])); ?>">View all</a>
    </div>
    <?php if (!$pendingRows): ?>
      <p class="page-sub">No pending payments.</p>
    <?php else: ?>
      <div class="table-scroll">
        <table class="plain">
          <thead>
            <tr>
              <th>ID</th><th>Participant</th><th>Phone</th>
              <th>Organisation</th><th>Registered</th><th>Due days</th><th>Remaining</th>
            </tr>
          </thead>
          <tbody>
          <?php $pendingSum = 0; foreach ($pendingRows as $app):
              $pendingSum += (float) $app['exam_fee'];
              $name = trim($app['first_name'].' '.$app['middle_name'].' '.$app['last_name']);
              $due = (int) $app['due_days'];
          ?>
            <tr>
              <td><a href="view_application.php?id=<?php echo (int)$app['id']; ?>">#<?php echo (int)$app['id']; ?></a></td>
              <td><?php echo htmlspecialchars($name); ?></td>
              <td><?php echo htmlspecialchars((string)$app['mobile']); ?></td>
              <td><?php echo htmlspecialchars((string)$app['school_name']); ?></td>
              <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($app['created_at']))); ?></td>
              <td><span class="badge <?php echo $due>30?'failed':($due>7?'pending':'paid'); ?>"><?php echo $due; ?>d</span></td>
              <td>₹<?php echo number_format((float)$app['exam_fee'], 0); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="6">Total outstanding (this list)</td>
              <td>₹<?php echo number_format($pendingSum, 0); ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="card" id="registrationList">
    <div class="card-head">
      <h3>Registration</h3>
      <div id="exportBtns" class="actions">
        <a class="btn sm" href="admin_export.php?format=excel&amp;<?php echo htmlspecialchars($exportQs); ?>">Excel</a>
        <a class="btn sm gold" href="admin_export.php?format=pdf&amp;<?php echo htmlspecialchars($exportQs); ?>" target="_blank">PDF</a>
        <a class="btn sm gray" href="admin_export.php?format=csv&amp;<?php echo htmlspecialchars($exportQs); ?>">CSV</a>
      </div>
    </div>
    <form class="filters" method="get" id="filterForm">
      <input type="hidden" name="tab" value="list">
      <input type="hidden" name="range" value="<?php echo htmlspecialchars($range); ?>">
      <input type="search" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search name / mobile / organisation">
      <select name="filter" onchange="this.form.submit()">
        <option value="all" <?php echo $current_filter==='all'?'selected':''; ?>>All status</option>
        <option value="paid" <?php echo $current_filter==='paid'?'selected':''; ?>>Paid</option>
        <option value="pending" <?php echo $current_filter==='pending'?'selected':''; ?>>Pending</option>
        <option value="failed" <?php echo $current_filter==='failed'?'selected':''; ?>>Failed</option>
      </select>
      <select name="class" onchange="this.form.submit()">
        <option value="">All roles</option>
        <?php foreach ($class_labels as $num=>$label): ?>
          <option value="<?php echo htmlspecialchars((string)$num); ?>" <?php echo $classFilter===(string)$num?'selected':''; ?>><?php echo htmlspecialchars((string)$label); ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn" type="submit">Search</button>
      <a class="btn gray" href="admin_dashboard.php?tab=list">Reset</a>
    </form>

    <div class="table-wrap">
      <table id="applicationsTable" class="display" style="width:100%">
        <thead>
          <tr>
            <th>#</th>
            <th>ID</th>
            <th>Participant</th>
            <th>Organisation Name</th>
            <th>Role</th>
            <th>Mobile</th>
            <th>Fee</th>
            <th>Status</th>
            <th>Registered</th>
            <th></th>
          </tr>
        </thead>
      </table>
    </div>
    <div class="app-cards" id="appCards"><p class="page-sub">Loading registrations…</p></div>
  </div>
</div>
<?php
$scripts = '<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const DASH = '.json_encode($chartPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP).';
$(function(){
  if (typeof Chart !== "undefined") {
  Chart.defaults.font.family = \'"Plus Jakarta Sans", "Segoe UI", sans-serif\';
  Chart.defaults.color = "#5c6b7a";
  Chart.defaults.plugins.legend.labels.boxWidth = 10;
  Chart.defaults.plugins.legend.labels.padding = 12;
  const colors = { blue:"#0058F0", orange:"#3D7FFF", green:"#0F9D58", warn:"#c56a00", bad:"#c62828", pale:"#cfe0ff" };
  function scales(){
    return { x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 7 } }, y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: "#eef2f6" } } };
  }
  function make(id, config){
    const el = document.getElementById(id);
    if (!el) return;
    config.options = Object.assign({ responsive: true, maintainAspectRatio: false, animation: false }, config.options || {});
    return new Chart(el, config);
  }
  function donut(id, labels, data, palette, showLegend){
    make(id, {
      type: "doughnut",
      data: { labels: labels, datasets: [{ data: data, backgroundColor: palette, borderWidth: 0, hoverOffset: 2 }] },
      options: { cutout: "70%", plugins: { legend: { display: !!showLegend, position: "bottom" } } }
    });
  }
  donut("statusDonut", ["Paid","Pending","Failed"], DASH.status, [colors.green, colors.warn, colors.bad], false);
  if (DASH.boardLabels && DASH.boardLabels.length) donut("boardDonut", DASH.boardLabels, DASH.boardCnt, [colors.blue, colors.orange, "#3b82f6", "#0e8f7a", "#64748b", colors.pale], true);
  if (DASH.mediumLabels && DASH.mediumLabels.length) donut("mediumDonut", DASH.mediumLabels, DASH.mediumCnt, [colors.blue, colors.orange, "#3b82f6", "#0e8f7a", "#64748b", colors.pale], true);
  if (DASH.classLabels && DASH.classLabels.length) {
    make("classChart", {
      type: "bar",
      data: {
        labels: DASH.classLabels,
        datasets: [
          { label: "Forms", data: DASH.classTotal, backgroundColor: "rgba(13,59,140,.18)", borderRadius: 4, maxBarThickness: 22 },
          { label: "Paid", data: DASH.classPaid, backgroundColor: colors.blue, borderRadius: 4, maxBarThickness: 22 }
        ]
      },
      options: { plugins: { legend: { position: "bottom" } }, scales: scales() }
    });
  }
  make("trendChart", {
    type: "bar",
    data: {
      labels: DASH.trendLabels,
      datasets: [
        { label: "Forms", data: DASH.trendTotal, backgroundColor: "rgba(13,59,140,.18)", borderRadius: 4, maxBarThickness: 18 },
        { label: "Paid", data: DASH.trendPaid, backgroundColor: colors.blue, borderRadius: 4, maxBarThickness: 18 }
      ]
    },
    options: { plugins: { legend: { position: "bottom" } }, scales: scales() }
  });
  make("countChart", {
    type: "bar",
    data: {
      labels: DASH.monthLabels,
      datasets: [
        { label: "Forms", data: DASH.monthCnt, backgroundColor: colors.blue, borderRadius: 6, maxBarThickness: 22 },
        { label: "Paid", data: DASH.monthPaid, backgroundColor: colors.orange, borderRadius: 6, maxBarThickness: 22 }
      ]
    },
    options: { plugins: { legend: { position: "bottom" } }, scales: scales() }
  });
  make("amountChart", {
    type: "line",
    data: {
      labels: DASH.monthLabels,
      datasets: [
        { label: "Form amount", data: DASH.monthAmt, borderColor: colors.blue, backgroundColor: "rgba(13,59,140,.12)", fill: true, tension: .35, pointRadius: 3, borderWidth: 2 },
        { label: "Collected", data: DASH.monthPaidAmt, borderColor: colors.orange, backgroundColor: "rgba(241,90,34,.12)", fill: true, tension: .35, pointRadius: 3, borderWidth: 2 }
      ]
    },
    options: { plugins: { legend: { position: "bottom" } }, scales: { x: scales().x, y: { beginAtZero: true, grid: { color: "#eef2f6" } } } }
  });
  }
  if (!$("#applicationsTable").length) return;
  $("#applicationsTable").DataTable({
    serverSide: true,
    processing: true,
    scrollX: true,
    ajax: {
      url: "admin_dashboardtest_data.php",
      type: "POST",
      data: function(d){
        d.status = $("select[name=filter]").val();
        d.class = $("select[name=class]").val();
        d.inst = $("select[name=inst]").val();
        d.size = $("select[name=size]").val();
        d.event = $("select[name=event]").val();
        d.q = $("input[name=q]").val();
        d.range = $("input[name=range]").val() || "all";
      },
      dataSrc: function(json){
        var cards = "";
        (json.data || []).forEach(function(row){
          cards += "<div class=\\"app-card\\"><b>"+row[2]+"</b><div class=\\"app-meta\\">#"+row[1]+" · "+row[4]+" · "+row[5]+"<br>"+row[3]+" · "+row[6]+" · "+row[7]+"</div><div class=\\"actions\\" style=\\"margin-top:8px;\\">"+row[9]+"</div></div>";
        });
        document.getElementById("appCards").innerHTML = cards || "<p>No registrations found.</p>";
        return json.data;
      }
    },
    order:[[1,"desc"]],
    pageLength: 15,
    columns: [
      {data:0},{data:1},{data:2},{data:3},{data:4},{data:5},{data:6},{data:7},
      {data:8},{data:9,orderable:false}
    ]
  });
});
</script>';
panel_end($scripts);
