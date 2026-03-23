<?php
$pageTitle = 'Manage FAQs - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle add/edit FAQ
if (isset($_POST['save_faq'])) {
    $faqId = $_POST['faq_id'] ?? null;
    $question = $_POST['question'] ?? '';
    $answer = $_POST['answer'] ?? '';
    $category = $_POST['category'] ?? '';
    $sortOrder = $_POST['sort_order'] ?? 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($question && $answer) {
        if ($faqId) {
            // Update existing FAQ
            $stmt = $db->prepare("UPDATE faqs SET question = ?, answer = ?, category = ?, sort_order = ?, is_active = ? WHERE faq_id = ?");
            $stmt->execute([$question, $answer, $category, $sortOrder, $isActive, $faqId]);
            $_SESSION['success'] = 'FAQ updated successfully';
        } else {
            // Add new FAQ
            $stmt = $db->prepare("INSERT INTO faqs (question, answer, category, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$question, $answer, $category, $sortOrder, $isActive]);
            $_SESSION['success'] = 'FAQ added successfully';
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('admin-faqs.php');
}

// Handle delete FAQ
if (isset($_POST['delete_faq'])) {
    $faqId = $_POST['faq_id'] ?? 0;
    if ($faqId) {
        $stmt = $db->prepare("DELETE FROM faqs WHERE faq_id = ?");
        if ($stmt->execute([$faqId])) {
            $_SESSION['success'] = 'FAQ deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete FAQ';
        }
    }
    redirect('admin-faqs.php');
}

// Get filter parameters
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM faqs WHERE 1=1";
$params = [];

if ($categoryFilter) {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
}

if ($statusFilter !== '') {
    $sql .= " AND is_active = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $sql .= " AND (question LIKE ? OR answer LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY sort_order ASC, faq_id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$faqs = $stmt->fetchAll();

// Get categories for filter
$categories = $db->query("SELECT DISTINCT category FROM faqs WHERE category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Status counts
$statusCounts = $db->query("SELECT is_active, COUNT(*) as count FROM faqs GROUP BY is_active")->fetchAll(PDO::FETCH_KEY_PAIR);

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <button type="button" onclick="openFaqModal()" class="btn btn-primary">Add New FAQ</button>
        </div>

        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px;">
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $statusCounts[1] ?? 0; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Active FAQs</p>
                    </div>
                    <i class="fas fa-check-circle" style="font-size: 32px; color: var(--success-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--danger-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $statusCounts[0] ?? 0; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Inactive FAQs</p>
                    </div>
                    <i class="fas fa-times-circle" style="font-size: 32px; color: var(--danger-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo count($faqs); ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Total FAQs</p>
                    </div>
                    <i class="fas fa-question-circle" style="font-size: 32px; color: var(--primary-color);"></i>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search FAQs..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Category</label>
                    <select name="category" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>><?php echo ucfirst(htmlspecialchars($cat)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All</option>
                        <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-faqs.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- FAQs Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">FAQs (<?php echo count($faqs); ?>)</h3>
            </div>

            <?php if (count($faqs) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Order</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Question</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Category</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faqs as $faq): ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <span style="font-weight: 600; color: var(--primary-color);"><?php echo $faq['sort_order']; ?></span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 500; max-width: 300px;"><?php echo htmlspecialchars($faq['question']); ?></div>
                                <div style="font-size: 12px; color: #666; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars(substr($faq['answer'], 0, 80)) . '...'; ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: #e3f2fd; color: #1976d2;">
                                    <?php echo ucfirst(htmlspecialchars($faq['category'])); ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $faq['is_active'] ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $faq['is_active'] ? '#155724' : '#721c24'; ?>;">
                                    <?php echo $faq['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" onclick="editFaq(<?php echo htmlspecialchars(json_encode($faq)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Edit</button>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this FAQ?');">
                                        <input type="hidden" name="faq_id" value="<?php echo $faq['faq_id']; ?>">
                                        <button type="submit" name="delete_faq" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="padding: 60px; text-align: center;">
                <i class="fas fa-question-circle" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No FAQs found</h3>
                <p style="color: #999;">Add your first FAQ</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- FAQ Modal -->
<div id="faqModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add New FAQ</h3>
            <button onclick="closeFaqModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" style="padding: 30px;">
            <input type="hidden" name="faq_id" id="faq_id">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Question *</label>
                <input type="text" name="question" id="question" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Answer *</label>
                <textarea name="answer" id="answer" rows="5" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Category</label>
                    <input type="text" name="category" id="category" placeholder="e.g., reservations, dining, services" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Sort Order</label>
                    <input type="number" name="sort_order" id="sort_order" min="0" value="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked style="width: 18px; height: 18px;">
                    <span style="font-size: 14px;">Active</span>
                </label>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeFaqModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="save_faq" class="btn btn-primary">Save FAQ</button>
            </div>
        </form>
    </div>
</div>

<script>
function openFaqModal() {
    document.getElementById('faqModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Add New FAQ';
    document.getElementById('faq_id').value = '';
    document.getElementById('question').value = '';
    document.getElementById('answer').value = '';
    document.getElementById('category').value = '';
    document.getElementById('sort_order').value = '0';
    document.getElementById('is_active').checked = true;
}

function editFaq(faq) {
    document.getElementById('faqModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit FAQ';
    document.getElementById('faq_id').value = faq.faq_id;
    document.getElementById('question').value = faq.question;
    document.getElementById('answer').value = faq.answer;
    document.getElementById('category').value = faq.category || '';
    document.getElementById('sort_order').value = faq.sort_order || 0;
    document.getElementById('is_active').checked = faq.is_active == 1;
}

function closeFaqModal() {
    document.getElementById('faqModal').style.display = 'none';
}

document.getElementById('faqModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeFaqModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
