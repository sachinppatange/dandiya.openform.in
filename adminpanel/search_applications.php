<?php
session_start();
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/wa_config.php';
require_once __DIR__ . '/../includes/panel_layout.php';

if (empty($_SESSION['admin_auth_user'])) {
    header('Location: admin_login.php?next=search_applications.php');
    exit;
}

panel_start([
    'title' => 'Search forms',
    'role' => 'admin',
    'active' => 'search',
    'name' => get_admin_name(),
    'phone' => local_phone_display($_SESSION['admin_auth_user'] ?? ''),
    'asset_prefix' => '../',
    'links' => admin_nav_links(),
    'head' => '<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">',
]);
?>
<div class="hero">
  <div>
    <h2>Find a form fast</h2>
    <p>Search by participant name, mobile or organisation name.</p>
  </div>
</div>
<div class="card">
  <div class="card-head"><h3>Search results</h3></div>
  <div class="table-wrap">
    <table id="appTable" class="display" style="width:100%">
      <thead>
        <tr>
          <th>ID</th><th>Name</th><th>Role</th><th>Mobile</th><th>Organisation Name</th><th>Status</th><th>Date</th><th></th>
        </tr>
      </thead>
    </table>
  </div>
  <div class="app-cards" id="appCards"><p>Search on this page, then results appear here on mobile.</p></div>
</div>
<?php
$scripts = '<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(function(){
  $("#appTable").DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: "search_applications_data.php", type: "POST",
      dataSrc: function(json){
        var cards = "";
        (json.data || []).forEach(function(row){
          cards += "<div class=\\"app-card\\"><b>"+row[1]+"</b><div class=\\"app-meta\\">#"+row[0]+" · "+row[2]+" · "+row[3]+"<br>"+row[4]+" · "+row[5]+"</div><div class=\\"actions\\" style=\\"margin-top:8px;\\">"+row[7]+"</div></div>";
        });
        document.getElementById("appCards").innerHTML = cards || "<p>No forms found.</p>";
        return json.data;
      }
    },
    pageLength: 15,
    order: [[0, "desc"]]
  });
});
</script>';
panel_end($scripts);
