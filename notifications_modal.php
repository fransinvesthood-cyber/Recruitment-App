<?php
include('config.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Unauthorized');
}

$user_id = (int)$_SESSION['user_id'];

// Pagination inside modal
$perPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Total
$totalStmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ?");
$totalStmt->bind_param('i', $user_id);
$totalStmt->execute();
$totalRow = $totalStmt->get_result()->fetch_assoc();
$total = (int)($totalRow['total'] ?? 0);
$totalPages = max(1, (int)ceil($total / $perPage));

// List
$stmt = $conn->prepare(
    "SELECT notification_id, message, is_read, created_at, type, reference_id
     FROM notifications
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT ? OFFSET ?"
);
$stmt->bind_param('iii', $user_id, $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Unread
$unreadStmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
$unreadStmt->bind_param('i', $user_id);
$unreadStmt->execute();
$unreadRow = $unreadStmt->get_result()->fetch_assoc();
$unreadCount = (int)($unreadRow['unread_count'] ?? 0);

// Render only the modal body
?>
<div class="modal-body" style="padding:16px 18px;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:12px;">
        <div>
            <div style="font-weight:800; color:#667eea; font-size:16px;">Notifications</div>
            <div style="color:#6c757d; font-size:12px; margin-top:2px;">Total: <strong><?php echo (int)$total; ?></strong> • Unread: <strong id="notifUnreadCountModal"><?php echo (int)$unreadCount; ?></strong></div>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <button class="btn" type="button" id="markAllReadBtnModal" style="padding:9px 14px; border-radius:10px; border:none; cursor:pointer; background:linear-gradient(135deg,#667eea 0%, #c9a9eaff 100%); color:#fff; font-weight:700;">
                <i class='bx bx-check' style="margin-right:6px;"></i> Mark all read
            </button>
        </div>
    </div>

    <?php if (count($notifications) === 0): ?>
        <div class="empty" style="padding:50px 10px; text-align:center; color:#6c757d;">
            <i class='bx bx-bell-off' style="font-size:44px; opacity:0.6; display:block; margin-bottom:10px;"></i>
            <div>No notifications found.</div>
        </div>
    <?php else: ?>
        <div style="max-height:55vh; overflow:auto; padding-right:6px;">
            <table style="width:100%; border-collapse:separate; border-spacing:0;">
                <thead>
                    <tr>
                        <th style="text-align:left; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; color:#667eea; padding:10px 10px; background:#fafbff; border-bottom:1px solid #eef2f7;">Notification</th>
                        <th style="text-align:left; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; color:#667eea; padding:10px 10px; background:#fafbff; border-bottom:1px solid #eef2f7; width:140px;">Time</th>
                        <th style="text-align:right; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; color:#667eea; padding:10px 10px; background:#fafbff; border-bottom:1px solid #eef2f7; width:220px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $n): ?>
                        <?php
                            $isRead = !empty($n['is_read']);
                            $notificationId = (int)$n['notification_id'];
                            $type = $n['type'] ?? 'general';
                            $referenceId = $n['reference_id'] ?? '';
                            $createdAt = $n['created_at'] ? date('M d, H:i', strtotime($n['created_at'])) : '';
                        ?>
                        <tr style="border-bottom:1px solid #eef2f7;">
                            <td style="padding:12px 10px; vertical-align:top;">
                                <div style="font-size:14px; line-height:1.35;"><?php echo htmlspecialchars($n['message'] ?? ''); ?></div>
                                <div style="color:#6c757d; font-size:12px; margin-top:6px; display:flex; gap:10px; flex-wrap:wrap;">
                                    <span>Type: <strong><?php echo htmlspecialchars($type); ?></strong></span>
                                    <?php if (!empty($referenceId)): ?>
                                        <span>Ref: <strong><?php echo htmlspecialchars((string)$referenceId); ?></strong></span>
                                    <?php endif; ?>
                                    <span style="padding:4px 10px; border-radius:999px; background:<?php echo $isRead ? '#e9ecef' : 'rgba(102,126,234,0.12)'; ?>; color:<?php echo $isRead ? '#333' : '#667eea'; ?>; font-weight:800; font-size:12px;">
                                        <?php echo $isRead ? 'Read' : 'Unread'; ?>
                                    </span>
                                </div>
                            </td>
                            <td style="padding:12px 10px; vertical-align:top; color:#6c757d; font-size:12px;">
                                <?php echo htmlspecialchars($createdAt); ?>
                            </td>
                            <td style="padding:12px 10px; vertical-align:top;">
                                <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
                                    <?php if (!$isRead): ?>
                                        <button class="mark-btn" type="button" data-notification-id-modal="<?php echo $notificationId; ?>" style="border:none; cursor:pointer; border-radius:10px; padding:9px 12px; background:rgba(102,126,234,0.1); color:#667eea; font-weight:800;">
                                            <i class='bx bx-check' style="margin-right:6px;"></i> Mark as read
                                        </button>
                                    <?php else: ?>
                                        <button class="mark-btn" type="button" disabled style="border:none; cursor:not-allowed; border-radius:10px; padding:9px 12px; background:#e9ecef; color:#6c757d; font-weight:800;">
                                            <i class='bx bx-check' style="margin-right:6px;"></i> Read
                                        </button>
                                    <?php endif; ?>

                                    <button class="delete-btn" type="button" data-notification-id-modal="<?php echo $notificationId; ?>" style="border:none; cursor:pointer; border-radius:10px; padding:9px 12px; background:rgba(220,53,69,0.10); color:#dc3545; font-weight:800;">
                                        <i class='bx bx-trash' style="margin-right:6px;"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:14px 2px 0;">
            <div style="color:#6c757d; font-size:12px;">Page <strong style="color:#667eea;"><?php echo (int)$page; ?></strong> / <?php echo (int)$totalPages; ?></div>
            <div style="display:flex; gap:10px;">
                <?php if ($page > 1): ?>
                    <button class="modal-pager-btn" type="button" data-page="<?php echo (int)($page-1); ?>" style="border:1px solid #e6eaf1; background:#fff; border-radius:10px; padding:8px 12px; cursor:pointer;">Previous</button>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <button class="modal-pager-btn" type="button" data-page="<?php echo (int)($page+1); ?>" style="border:1px solid #e6eaf1; background:#fff; border-radius:10px; padding:8px 12px; cursor:pointer;">Next</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
(function(){
  // Bind modal actions after content is loaded
  const root = document.getElementById('notificationsModalContent');
  if (!root) return;

  function bumpUnreadPill(delta){
    const pill = document.getElementById('notificationBadge');
    if (!pill) return;
    const cur = parseInt(pill.textContent || '0', 10) || 0;
    const next = Math.max(0, cur + delta);
    if (next === 0) {
      pill.style.display = 'none';
    } else {
      pill.style.display = 'flex';
      pill.textContent = next;
    }

    const modalCount = document.getElementById('notifUnreadCountModal');
    if (modalCount) modalCount.textContent = next;
  }

  root.querySelectorAll('[data-notification-id-modal]').forEach(btn => {
    const id = btn.getAttribute('data-notification-id-modal');
    const tr = btn.closest('tr');

    btn.addEventListener('click', function(){
      // Mark
      if (btn.classList.contains('mark-btn') && !btn.disabled) {
        fetch('mark_notifications_read.php', {
          method:'POST',
          headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body:'notification_id=' + encodeURIComponent(id)
        })
        .then(r=>r.json())
        .then(data=>{
          if(!data || !data.success) throw new Error(data && data.message ? data.message : 'Failed');
          // update UI simply by reloading content
          loadModalPage(currentModalPage);
        })
        .catch(()=>alert('Failed to mark notification as read'));
        return;
      }

      // Delete
      if (btn.classList.contains('delete-btn')) {
        if (!confirm('Delete this notification?')) return;
        fetch('delete_notifications.php', {
          method:'POST',
          headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body:'notification_id=' + encodeURIComponent(id)
        })
        .then(r=>r.json())
        .then(data=>{
          if(!data || !data.success) throw new Error(data && data.message ? data.message : 'Failed');
          // if unread, decrement unread count (best-effort)
          if (tr && tr.innerText.toLowerCase().includes('unread')) bumpUnreadPill(-1);
          loadModalPage(currentModalPage);
        })
        .catch(()=>alert('Failed to delete notification'));
      }
    });
  });

  // Pager
  root.querySelectorAll('.modal-pager-btn').forEach(p => {
    p.addEventListener('click', function(){
      const nextPage = parseInt(this.getAttribute('data-page') || '1', 10);
      loadModalPage(nextPage);
    });
  });

  // Mark all
  const markAllBtn = root.querySelector('#markAllReadBtnModal');
  if (markAllBtn) {
    markAllBtn.addEventListener('click', function(){
      if (!confirm('Mark all notifications as read?')) return;
      fetch('mark_notifications_read.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'mark_all=1'
      })
      .then(r=>r.json())
      .then(data=>{
        if(!data || !data.success) throw new Error(data && data.message ? data.message : 'Failed');
        loadModalPage(currentModalPage);
      })
      .catch(()=>alert('Failed to mark all read'));
    });
  }
})();
</script>

