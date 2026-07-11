<?php
// admin/resources.php
require_once '../config.php';
check_auth('manager');

$error = '';
$success = '';

// Handle POST actions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $category = $_POST['category'] ?? '';
        $pickup_address = trim($_POST['pickup_address'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        
        // Handle image file upload
        $image = 'facility.jpg';
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['image_file']['tmp_name'];
            $file_name = $_FILES['image_file']['name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $upload_dir = __DIR__ . '/../uploads/';
                if (!is_dir($upload_dir)) {
                    @mkdir($upload_dir, 0755, true);
                }
                $new_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file_name);
                if (move_uploaded_file($file_tmp, $upload_dir . $new_name)) {
                    $image = $new_name;
                }
            } else {
                $error = 'Only JPG, JPEG, and PNG files are allowed.';
            }
        } else {
            $error = 'Please upload a resource image.';
        }

        if (empty($error)) {
            if (empty($name) || empty($category) || empty($pickup_address) || empty($description) || $price <= 0) {
                $error = 'All fields are required and price must be greater than 0.';
            } else {
                add_resource($name, $category, $description, $price, $image, $pickup_address);
                $success = 'Resource added successfully!';
            }
        }
    } elseif ($action === 'edit') {
        $id = $_POST['resource_id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $category = $_POST['category'] ?? '';
        $pickup_address = trim($_POST['pickup_address'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $status = $_POST['status'] ?? 'Available';

        if (empty($id) || !get_resource_by_id($id)) {
            $error = 'Resource not found.';
        } elseif (empty($name) || empty($category) || empty($pickup_address) || empty($description) || $price <= 0) {
            $error = 'All fields are required and price must be greater than 0.';
        } else {
            edit_resource($id, $name, $category, $description, $price, $status, $pickup_address);
            $success = 'Resource updated successfully!';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['resource_id'] ?? '';
        
        if (empty($id) || !get_resource_by_id($id)) {
            $error = 'Resource not found.';
        } else {
            delete_resource($id);
            $success = 'Resource deleted successfully!';
        }
    }
}

$filter_category = $_GET['filter_category'] ?? 'All';
$filter_search = trim($_GET['search'] ?? '');
$manager_scope = $_SESSION['manager_scope'] ?? get_manager_scope_by_email($_SESSION['email'] ?? '');
$manager_labels = get_manager_display_labels($manager_scope);
$current_manager_id = $_SESSION['user_id'] ?? '';

$filtered_resources = [];
foreach (get_all_resources() as $id => $res) {
    if (($res['uploaded_by'] ?? '') !== $current_manager_id) {
        continue;
    }
    if ($filter_category !== 'All' && $res['category'] !== $filter_category) {
        continue;
    }
    if ($filter_search !== '' && stripos($res['name'], $filter_search) === false && stripos($res['description'], $filter_search) === false) {
        continue;
    }
    $filtered_resources[$id] = $res;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Uni.Book - Manager Resources</title>
  <link rel="stylesheet" href="../assets/css/style.css?v=2.5">
</head>
<body class="sidebar-closed">

  <!-- FIXED TOP HEADER -->
  <header class="dashboard-header">
    <div class="header-left">
      <button class="toggle-btn" onclick="toggleSidebar()" aria-label="Toggle Sidebar">☰</button>
    </div>
    <div class="header-center">
      <a href="<?php echo url('index.php'); ?>" class="brand">
        <div class="brand-logo">Uni<span class="brand-dot-center"></span><span class="brand-book">Book</span></div>
        <span class="brand-sub">University Booking System</span>
      </a>
    </div>
  </header>

  <div class="dashboard-layout">
    
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebarMenu">
      <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-link nav-item"><?php echo get_icon('dashboard'); ?> Dashboard</a>
        <a href="resources.php" class="nav-link nav-item active"><?php echo get_icon('resources'); ?> Manage Resources</a>
        <a href="bookings.php" class="nav-link nav-item"><?php echo get_icon('my-bookings'); ?> View Bookings</a>
        <a href="../logout.php" class="nav-link nav-item" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;"><?php echo get_icon('logout'); ?> Logout</a>
      </nav>
      <div class="sidebar-user">
        <div class="user-avatar">MG</div>
        <div class="user-meta">
          <span class="user-name"><?php echo htmlspecialchars($manager_labels['department']); ?></span>
          <span class="user-role"><?php echo htmlspecialchars($manager_labels['role']); ?></span>
        </div>
      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-main">
      
      <!-- PAGE HEADER -->
      <div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
          <h1 class="page-title"> ✎ Manage Resources</h1>
          <p class="page-desc">Create, modify, or remove campus facilities and services for your department<?php echo $manager_scope ? ' (' . htmlspecialchars($manager_scope) . ')' : ''; ?>.</p>
        </div>
        <button class="btn-main" style="width:auto; padding:10px 20px; font-size:0.9rem;" onclick="openAddModal()">
          ✚ Add Resource
        </button>
      </div>

      <!-- Alerts -->
      <?php if (!empty($error)): ?>
        <div class="alert err" style="display:block;"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if (!empty($success)): ?>
        <div class="alert ok" style="display:block;"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <!-- FILTER BAR -->
      <div class="dashboard-panel" style="padding:16px 24px; margin-bottom:24px; display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
        <form action="resources.php" method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; width:100%;">
          <div style="display:flex; flex-direction:column; gap:4px; min-width:180px;">
            <span style="font-size:0.75rem; font-weight:700; color:var(--teal-dark);">Category</span>
            <select name="filter_category" onchange="this.form.submit()" style="padding:8px 12px; background:#fff; border:1px solid var(--border); border-radius:8px; min-width:180px;">
              <?php if ($manager_scope): ?>
                <option value="<?php echo htmlspecialchars($manager_scope); ?>" <?php echo $filter_category === $manager_scope ? 'selected' : ''; ?>><?php echo htmlspecialchars($manager_scope); ?></option>
              <?php else: ?>
                <option value="All" <?php echo $filter_category === 'All' ? 'selected' : ''; ?>>All Categories</option>
                <option value="Facilities" <?php echo $filter_category === 'Facilities' ? 'selected' : ''; ?>>Facilities</option>
                <option value="Vehicles" <?php echo $filter_category === 'Vehicles' ? 'selected' : ''; ?>>Vehicles</option>
                <option value="Personnel" <?php echo $filter_category === 'Personnel' ? 'selected' : ''; ?>>Personnel</option>
                <option value="Equipment" <?php echo $filter_category === 'Equipment' ? 'selected' : ''; ?>>Equipment</option>
              <?php endif; ?>
            </select>
          </div>

          <div style="display:flex; flex-direction:column; gap:4px; flex:1; min-width:220px;">
            <span style="font-size:0.75rem; font-weight:700; color:var(--teal-dark);">Search Resources</span>
            <input type="search" name="search" value="<?php echo htmlspecialchars($filter_search); ?>" placeholder="Search by name or description..." style="padding:8px 12px; border:1px solid var(--border); border-radius:8px; width:100%;">
          </div>

          <div style="display:flex; gap:12px; align-items:center; margin-left:auto;">
            <button type="submit" class="btn-secondary" style="padding:10px 18px;">Apply</button>
            <a href="resources.php" class="btn-main" style="padding:10px 18px;">Clear</a>
          </div>
        </form>
      </div>

      <!-- RESOURCES TABLE -->
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Resource Name</th>
              <th>Category</th>
              <th>Description</th>
              <th>Address / Pickup</th>
              <th>Price / Slot</th>
              <th>Status</th>
              <th style="text-align:center;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($filtered_resources as $id => $res): ?>
              <tr>
                <td style="font-weight:700; color:var(--teal-dark);"><?php echo htmlspecialchars($id); ?></td>
                <td style="font-weight:600;"><?php echo htmlspecialchars($res['name']); ?></td>
                <td><?php echo htmlspecialchars($res['category']); ?></td>
                <td style="max-width:300px; font-size:0.8rem; line-height:1.4; color:var(--muted);"><?php echo htmlspecialchars($res['description']); ?></td>
                <td style="max-width:240px; font-size:0.85rem; color:var(--muted);"><?php echo htmlspecialchars($res['pickup_address'] ?? '—'); ?></td>
                <td style="font-weight:600;"><?php echo format_currency($res['price']); ?></td>
                <td>
                  <span class="badge <?php echo $res['status'] === 'Available' ? 'confirmed' : 'rejected'; ?>">
                    <?php echo htmlspecialchars($res['status']); ?>
                  </span>
                </td>
                <td>
                  <div style="display:flex; gap:8px; justify-content:center;">
                    <!-- Edit Button -->
                    <button class="btn-secondary" style="padding:4px 8px; font-size:0.75rem;" 
                            onclick="openEditModal(
                              '<?php echo $id; ?>', 
                              '<?php echo htmlspecialchars($res['name'], ENT_QUOTES); ?>', 
                              '<?php echo $res['category']; ?>', 
                              '<?php echo htmlspecialchars($res['pickup_address'] ?? '', ENT_QUOTES); ?>', 
                              '<?php echo htmlspecialchars($res['description'], ENT_QUOTES); ?>', 
                              '<?php echo $res['price']; ?>', 
                              '<?php echo $res['status']; ?>'
                            )">
                      Edit
                    </button>
                    <!-- Delete Button -->
                    <form action="resources.php" method="POST" onsubmit="return confirmDelete('<?php echo htmlspecialchars($res['name'], ENT_QUOTES); ?>')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="resource_id" value="<?php echo $id; ?>">
                      <button type="submit" class="btn-danger" style="padding:4px 8px; font-size:0.75rem;">
                        Delete
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </main>
  </div>

  <!-- ADD RESOURCE MODAL -->
  <div class="modal-overlay" id="addModal">
    <div class="modal-box">
      <h2 class="modal-title">Add New Resource</h2>
      <p style="color:var(--muted); font-size:0.8rem; margin-bottom:20px;">Provide specifications for the new campus booking item.</p>
      
      <form action="resources.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">

        <div class="field">
          <label class="lbl">Resource Name *</label>
          <input type="text" name="name" placeholder="e.g. Seminar Hall C" required>
        </div>

        <div class="field">
          <label class="lbl">Category *</label>
          <select name="category" required>
            <?php if ($manager_scope): ?>
              <option value="<?php echo htmlspecialchars($manager_scope); ?>" selected><?php echo htmlspecialchars($manager_scope); ?></option>
            <?php else: ?>
              <option value="">-- Select Category --</option>
              <option value="Facilities">Facilities</option>
              <option value="Vehicles">Vehicles</option>
              <option value="Personnel">Personnel</option>
              <option value="Equipment">Equipment</option>
            <?php endif; ?>
          </select>
        </div>

        <div class="field">
          <label class="lbl">Address / Pickup *</label>
          <input type="text" name="pickup_address" placeholder="Enter address or pickup location" required>
        </div>

        <div class="field">
          <label class="lbl">Description *</label>
          <textarea name="description" rows="3" placeholder="Enter resource description..." required></textarea>
        </div>

        <div class="two-col">
          <div class="field">
            <label class="lbl">Price per Slot ($) *</label>
            <input type="number" name="price" step="0.01" min="1" placeholder="50.00" required>
          </div>
          <div class="field">
            <label class="lbl">Upload Picture * (PNG/JPG only)</label>
            <input type="file" name="image_file" accept=".png, .jpg, .jpeg" required>
          </div>
        </div>

        <div class="modal-footer" style="padding-top:16px;">
          <button type="button" class="btn-secondary" onclick="closeAddModal()">Cancel</button>
          <button type="submit" class="btn-main" style="width:auto; padding:10px 20px;">Save Resource</button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT RESOURCE MODAL -->
  <div class="modal-overlay" id="editModal">
    <div class="modal-box">
      <h2 class="modal-title">Edit Resource</h2>
      <p style="color:var(--muted); font-size:0.8rem; margin-bottom:20px;">Modify the details and availability status of the resource.</p>
      
      <form action="resources.php" method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="resource_id" id="edit-resource-id">

        <div class="field">
          <label class="lbl">Resource Name *</label>
          <input type="text" name="name" id="edit-name" required>
        </div>

        <div class="field">
          <label class="lbl">Category *</label>
          <select name="category" id="edit-category" required>
            <?php if ($manager_scope): ?>
              <option value="<?php echo htmlspecialchars($manager_scope); ?>"><?php echo htmlspecialchars($manager_scope); ?></option>
            <?php else: ?>
              <option value="Facilities">Facilities</option>
              <option value="Vehicles">Vehicles</option>
              <option value="Personnel">Personnel</option>
              <option value="Equipment">Equipment</option>
            <?php endif; ?>
          </select>
        </div>

        <div class="field">
          <label class="lbl">Address / Pickup *</label>
          <input type="text" name="pickup_address" id="edit-pickup-address" placeholder="Enter address or pickup location" required>
        </div>

        <div class="field">
          <label class="lbl">Description *</label>
          <textarea name="description" id="edit-description" rows="3" required></textarea>
        </div>

        <div class="two-col">
          <div class="field">
            <label class="lbl">Price per Slot ($) *</label>
            <input type="number" name="price" id="edit-price" step="0.01" min="1" required>
          </div>
          <div class="field">
            <label class="lbl">Status *</label>
            <select name="status" id="edit-status" required>
              <option value="Available">Available</option>
              <option value="Unavailable">Unavailable</option>
            </select>
          </div>
        </div>

        <div class="modal-footer" style="padding-top:16px;">
          <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
          <button type="submit" class="btn-main" style="width:auto; padding:10px 20px;">Update Resource</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      document.body.classList.toggle('sidebar-closed');
    }

    // Confirmation dialog before deletion
    function confirmDelete(resName) {
      return confirm("⚠️ Are you sure you want to delete '" + resName + "'? This action cannot be undone.");
    }

    // Modal Operations
    function openAddModal() {
      document.getElementById('addModal').classList.add('open');
    }
    function closeAddModal() {
      document.getElementById('addModal').classList.remove('open');
    }

    function openEditModal(id, name, category, pickupAddress, description, price, status) {
      document.getElementById('edit-resource-id').value = id;
      document.getElementById('edit-name').value = name;
      document.getElementById('edit-category').value = category;
      document.getElementById('edit-pickup-address').value = pickupAddress;
      document.getElementById('edit-description').value = description;
      document.getElementById('edit-price').value = price;
      document.getElementById('edit-status').value = status;
      document.getElementById('editModal').classList.add('open');
    }
    function closeEditModal() {
      document.getElementById('editModal').classList.remove('open');
    }
  </script>


</body>
</html>


