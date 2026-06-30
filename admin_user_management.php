<?php
// Admin User Management (production-ready)
// Only Administrators can access.

include('config.php');
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';


// --- RBAC check (hard requirement) ---
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login_signup.php');
    exit();
}

$admin_user_id = (int)$_SESSION['user_id'];
$admin_username = $_SESSION['username'] ?? ($_SESSION['role'] ?? 'admin');
$admin_role = $_SESSION['role'];

if ($admin_role !== 'Admin') {
    http_response_code(403);
    die('Access denied. Administrators only.');
}

// Session message
$message = '';
$messageClass = '';

// ---- Logged-in admin welcome name ----
$fullname = '';
$sql_fullname = "SELECT fullname FROM users WHERE user_id = ? LIMIT 1";
$stmt_fullname = $conn->prepare($sql_fullname);
if ($stmt_fullname) {
    $stmt_fullname->bind_param('i', $admin_user_id);
    $stmt_fullname->execute();
    $stmt_fullname->bind_result($db_fullname);
    if ($stmt_fullname->fetch()) {
        $fullname = (string)$db_fullname;
    }
    $stmt_fullname->close();
}
if ($fullname === '') {
    $fullname = $admin_username;
}

// ---- Helpers ----
function json_details(array $arr): string {
    return json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function add_audit_log(mysqli $conn, int $admin_user_id, string $admin_username, string $action_type, int $target_user_id, string $target_username, ?string $ip, ?array $details = null): void {
    $details_json = $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

    $sql = "INSERT INTO user_audit_log (admin_user_id, admin_username, action_type, target_user_id, target_username, admin_ip_address, details)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return; // avoid breaking admin UI if audit table not present
    }
    $stmt->bind_param(
        "ississs",
        $admin_user_id,
        $admin_username,
        $action_type,
        $target_user_id,
        $target_username,
        $ip,
        $details_json
    );
    $stmt->execute();
    $stmt->close();
}

function enforce_strong_password(string $password, string &$error): bool {
    // Strong password: at least 8 chars, uppercase, lowercase, digit, special
    if (strlen($password) < 8) { $error = 'Password must be at least 8 characters long.'; return false; }
    if (!preg_match('/[A-Z]/', $password)) { $error = 'Password must include at least one uppercase letter.'; return false; }
    if (!preg_match('/[a-z]/', $password)) { $error = 'Password must include at least one lowercase letter.'; return false; }
    if (!preg_match('/\d/', $password)) { $error = 'Password must include at least one number.'; return false; }
    if (!preg_match('/[\W_]/', $password)) { $error = 'Password must include at least one special character.'; return false; }
    return true;
}

function normalize_role(string $role): ?string {
    // Your DB enum roles are: Applicant, Admin, Consultant.
    // Admin user-management page can create Admin, Consultant and Applicant.
    if ($role === 'Admin' || $role === 'Consultant' || $role === 'Applicant') return $role;
    return null;
}


function validate_email_format(string $email): bool {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

function find_user_by_username_or_email(mysqli $conn, string $username, string $email, ?int $ignore_user_id = null): array {
    // Returns ['username_taken'=>bool,'email_taken'=>bool]
    $u_sql = "SELECT user_id FROM users WHERE username = ?";
    $e_sql = "SELECT user_id FROM users WHERE email = ?";
    if ($ignore_user_id !== null) {
        $u_sql .= " AND user_id != ?";
        $e_sql .= " AND user_id != ?";
    }

    $username_taken = false;
    $email_taken = false;

    $u_stmt = $conn->prepare($u_sql);
    if ($ignore_user_id !== null) {
        $u_stmt->bind_param('si', $username, $ignore_user_id);
    } else {
        $u_stmt->bind_param('s', $username);
    }
    $u_stmt->execute();
    $u_stmt->store_result();
    $username_taken = $u_stmt->num_rows > 0;
    $u_stmt->close();

    $e_stmt = $conn->prepare($e_sql);
    if ($ignore_user_id !== null) {
        $e_stmt->bind_param('si', $email, $ignore_user_id);
    } else {
        $e_stmt->bind_param('s', $email);
    }
    $e_stmt->execute();
    $e_stmt->store_result();
    $email_taken = $e_stmt->num_rows > 0;
    $e_stmt->close();

    return [
        'username_taken' => $username_taken,
        'email_taken' => $email_taken,
    ];
}

// ---- Fetch dashboard counts (cards) ----
$counts = ['Admin' => 0, 'Consultant' => 0];
$counts_res = $conn->query("SELECT role, COUNT(*) AS cnt FROM users GROUP BY role");
if ($counts_res) {
    while ($r = $counts_res->fetch_assoc()) {
        if (isset($counts[$r['role']])) $counts[$r['role']] = (int)$r['cnt'];
    }
}

$admin_ip = $_SERVER['REMOTE_ADDR'] ?? null;

// Admin notifications (optional): if the system has a notifications table,
// we can create a notification for the affected user after activation/deactivation/deletion.
// This is intentionally non-fatal if the notifications table/columns do not exist.
function add_notification_safe(mysqli $conn, int $target_user_id, string $message, string $type = 'general', $reference_id = null): void {
    $ref = $reference_id === null ? null : (string)$reference_id;

    // Best-effort insert. If schema mismatches, it should fail silently.
    // Use bind_param types that match the placeholder count:
    // (1) i: target_user_id
    // (2) s: message
    // (3) s: type
    // (4) s: reference_id (nullable)
    $sql = "INSERT INTO notifications (user_id, message, is_read, created_at, type, reference_id)
            VALUES (?, ?, 0, NOW(), ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return;
    }

    $stmt->bind_param('isss', $target_user_id, $message, $type, $ref);

    try {
        $stmt->execute();
    } catch (Throwable $e) {
        // ignore
    }


    $stmt->close();
}



// ---- Handle actions ----
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create_user') {
    $full_name = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'Consultant';
    $account_status = $_POST['account_status'] ?? 'Active';

    $role_norm = normalize_role($role);
    if ($role_norm === null) {
        $message = 'Invalid role.';
        $messageClass = 'error';
    } elseif ($account_status !== 'Active' && $account_status !== 'Inactive') {
        $message = 'Invalid account status.';
        $messageClass = 'error';
    } elseif ($full_name === '' || $username === '' || $email === '' || $password === '' || $confirm_password === '') {
        $message = 'All fields are required.';
        $messageClass = 'error';
    } elseif (!validate_email_format($email)) {
        $message = 'Please enter a valid email address.';
        $messageClass = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $messageClass = 'error';
    } else {
        $pwd_error = '';
        if (!enforce_strong_password($password, $pwd_error)) {
            $message = $pwd_error;
            $messageClass = 'error';
        } else {
            $dupes = find_user_by_username_or_email($conn, $username, $email, null);
            if ($dupes['username_taken']) {
                $message = 'Username is already taken.';
                $messageClass = 'error';
            } elseif ($dupes['email_taken']) {
                $message = 'Email address is already in use.';
                $messageClass = 'error';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                $insert_sql = "INSERT INTO users (fullname, username, email, password, role, account_status, email_verified, verification_token, failed_attempts, last_failed_login)
                                VALUES (?, ?, ?, ?, ?, ?, 0, ?, 0, 0)";

                $stmt = $conn->prepare($insert_sql);
                if (!$stmt) {
                    $message = 'Database error (prepare failed).';
                    $messageClass = 'error';
                } else {
                        // For admin-created users, also send email verification
                    $verificationToken = bin2hex(random_bytes(32));

                    if ($stmt->bind_param(
                        "sssssss",
                        $full_name,
                        $username,
                        $email,
                        $hashed,
                        $role_norm,
                        $account_status,
                        $verificationToken
                    ) && $stmt->execute()) {
                        $target_user_id = (int)$stmt->insert_id;

                        // Ensure verification state for admin-created accounts
                        try {
                            $upd = $conn->prepare("UPDATE users SET email_verified = 0, verification_token = ? WHERE user_id = ? LIMIT 1");
                            if ($upd) {
                                $upd->bind_param('si', $verificationToken, $target_user_id);
                                $upd->execute();
                                $upd->close();
                            }
                        } catch (Throwable $e) {}

                        // Best-effort send email verification to the created user
                        try {
                            $verificationLink = "http://localhost/Recruitment-App/verify_email.php?token={$verificationToken}";
                            // If this app is hosted under a different base URL/path, also provide a relative URL fallback.
                            // (Some environments might not have /recruitment-project-phps configured as expected.)
                            $verificationLinkRelative = "verify_email.php?token={$verificationToken}";


                            $mail = new PHPMailer(true);
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'delanideco69@gmail.com';
                            $mail->Password = 'kyuqrccxdsqkkosb';
                            $mail->SMTPSecure = 'tls';
                            $mail->Port = 587;

                            $mail->setFrom('delanideco69@gmail.com', 'Recruitment Team');
                            $mail->addAddress($email);
                            $mail->Subject = 'Verify Your Email Address';
                            $mail->Body = "Welcome to our platform!\n\nPlease click the following link to verify your email address:\n{$verificationLink}\n\nIf you did not create this account, please ignore this email.";

                            $mail->send();
                        } catch (Throwable $e) {}

                        add_audit_log($conn, $admin_user_id, $admin_username, 'create', $target_user_id, $username, $admin_ip, [
                            'fullname' => $full_name,
                            'role' => $role_norm,
                            'account_status' => $account_status,
                            'email' => $email,
                        ]);

                        $message = 'User created successfully.';

                        $messageClass = 'success';

                        // Notify admin + affected user (best-effort)
                        add_notification_safe(
                            $conn,
                            $admin_user_id,
                            "A new user was created: {$username}.",
                            'general',
                            $target_user_id
                        );

                        add_notification_safe(
                            $conn,
                            $target_user_id,
                            "Your account has been created by an administrator.",
                            'general',
                            $target_user_id
                        );
                    } else {
                        $message = 'Error creating user: ' . $conn->error;
                        $messageClass = 'error';
                    }
                    $stmt->close();
                }
            }
        }
    }
}

if ($action === 'update_user') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $full_name = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? 'Consultant';
    $account_status = $_POST['account_status'] ?? 'Active';

    $role_norm = normalize_role($role);

    if ($user_id <= 0 || $full_name === '' || $email === '' || $username === '' || $role_norm === null) {
        $message = 'Invalid input.';
        $messageClass = 'error';
    } elseif (!validate_email_format($email)) {
        $message = 'Please enter a valid email address.';
        $messageClass = 'error';
    } elseif ($account_status !== 'Active' && $account_status !== 'Inactive') {
        $message = 'Invalid account status.';
        $messageClass = 'error';
    } else {
        $dupes = find_user_by_username_or_email($conn, $username, $email, $user_id);
        if ($dupes['username_taken']) {
            $message = 'Username is already taken.';
            $messageClass = 'error';
        } elseif ($dupes['email_taken']) {
            $message = 'Email address is already in use.';
            $messageClass = 'error';
        } else {
            $sql = "SELECT username FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $stmt->bind_result($old_username);
            $stmt->fetch();
            $stmt->close();

            $update_sql = "UPDATE users SET fullname = ?, username = ?, email = ?, role = ?, account_status = ? WHERE user_id = ?";

            $ensure_account_status = function(string $col) use ($conn): bool {
                $ensure_sql_local = "
                    SELECT COLUMN_NAME
                    FROM information_schema.columns
                    WHERE table_schema = DATABASE()
                      AND table_name = 'users'
                      AND column_name = ?
                ";
                $st = $conn->prepare($ensure_sql_local);
                if (!$st) return false;
                $st->bind_param('s', $col);
                $st->execute();
                $st->store_result();
                $exists = $st->num_rows > 0;
                $st->close();
                return $exists;
            };

            if (!$ensure_account_status('account_status')) {
                $conn->query("ALTER TABLE users ADD COLUMN account_status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'");
            }

            $u = $conn->prepare($update_sql);
            if (!$u) {
                $message = 'Database error (prepare failed).';
                $messageClass = 'error';
            } else {
                $u->bind_param('sssssi', $full_name, $username, $email, $role_norm, $account_status, $user_id);
                if ($u->execute()) {
                    add_audit_log($conn, $admin_user_id, $admin_username, 'update', $user_id, $username, $admin_ip, [
                        'old_username' => $old_username,
                        'fullname' => $full_name,
                        'role' => $role_norm,
                        'account_status' => $account_status,
                        'email' => $email,
                    ]);
                    $message = 'User updated successfully.';
                    $messageClass = 'success';
                } else {
                    $message = 'Error updating user: ' . $conn->error;
                    $messageClass = 'error';
                }
                $u->close();
            }
        }
    }
}

if ($action === 'set_activation') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $new_status = $_POST['account_status'] ?? 'Active';

    if ($user_id <= 0 || ($new_status !== 'Active' && $new_status !== 'Inactive')) {
        $message = 'Invalid activation request.';
        $messageClass = 'error';
    } else {
        $ensure_account_status = function(string $col) use ($conn): bool {
            $ensure_sql_local = "
                SELECT COLUMN_NAME
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = 'users'
                  AND column_name = ?
            ";
            $st = $conn->prepare($ensure_sql_local);
            if (!$st) return false;
            $st->bind_param('s', $col);
            $st->execute();
            $st->store_result();
            $exists = $st->num_rows > 0;
            $st->close();
            return $exists;
        };

        if (!$ensure_account_status('account_status')) {
            $conn->query("ALTER TABLE users ADD COLUMN account_status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'");
        }

        $username_sql = "SELECT username FROM users WHERE user_id = ?";
        $st = $conn->prepare($username_sql);
        $st->bind_param('i', $user_id);
        $st->execute();
        $st->bind_result($target_username);
        $st->fetch();
        $st->close();

        $up = $conn->prepare("UPDATE users SET account_status = ? WHERE user_id = ?");
        $up->bind_param('si', $new_status, $user_id);
if ($up->execute()) {
            $audit_type = ($new_status === 'Active') ? 'activation' : 'deactivation';
            add_audit_log($conn, $admin_user_id, $admin_username, $audit_type, $user_id, $target_username ?? 'unknown', $admin_ip, [
                'account_status' => $new_status,
            ]);

            // Notify affected user (best-effort)
            $actionText = ($new_status === 'Active') ? 'activated' : 'deactivated';

            // 1) Notify the affected user
            add_notification_safe(
                $conn,
                $user_id,
                "Your account has been {$actionText} by an administrator.",
                'general',
                null
            );

            // 2) Notify the admin who performed the action
            add_notification_safe(
                $conn,
                $admin_user_id,
                "You {$actionText} the account of {$target_username}.",
                'general',
                $user_id
            );

            $message = 'Account status updated.';
            $messageClass = 'success';
        } else {

            $message = 'Error updating account status: ' . $conn->error;
            $messageClass = 'error';
        }
        $up->close();
    }
}

if ($action === 'delete_user') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if ($user_id <= 0) {
        $message = 'Invalid user.';
        $messageClass = 'error';
    } else {
        if ($user_id === $admin_user_id) {
            $message = 'You cannot delete your own admin account.';
            $messageClass = 'error';
        } else {
            $uname = '';
            $st = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
            $st->bind_param('i', $user_id);
            $st->execute();
            $st->bind_result($uname);
            $st->fetch();
            $st->close();

            $conn->query("DELETE FROM user_audit_log WHERE target_user_id = " . (int)$user_id);

            $del = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $del->bind_param('i', $user_id);
            if ($del->execute()) {
                $message = 'User deleted successfully.';
                $messageClass = 'success';

                // Notify admin + (best-effort) affected user before deletion
                add_notification_safe(
                    $conn,
                    $admin_user_id,
                    "User was deleted: {$uname}.",
                    'general',
                    (int)$user_id
                );

                add_notification_safe(
                    $conn,
                    (int)$user_id,
                    "Your account was deleted by an administrator.",
                    'general',
                    (int)$user_id
                );
            } else {
                $message = 'Error deleting user: ' . $conn->error;
                $messageClass = 'error';
            }
            $del->close();
        }
    }
}

// ---- List users with search/filter/pagination ----
$search = trim($_GET['search'] ?? $_POST['search'] ?? '');
$role_filter = $_GET['role_filter'] ?? $_POST['role_filter'] ?? 'all';
$status_filter = $_GET['status_filter'] ?? $_POST['status_filter'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$page_size = 10;
$offset = ($page - 1) * $page_size;

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(username LIKE ? OR fullname LIKE ? OR email LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}

if ($role_filter !== 'all') {
    $role_filter = ($role_filter === 'Admin' || $role_filter === 'Consultant' || $role_filter === 'Applicant') ? $role_filter : 'all';
    if ($role_filter !== 'all') {
        $where[] = "role = ?";
        $params[] = $role_filter;
        $types .= 's';
    }
}


if ($status_filter !== 'all') {
    if ($status_filter === 'Active' || $status_filter === 'Inactive') {
        // Ensure account_status exists before filtering (avoid fatal error if column already exists)
        $ensure_account_status = function(string $col) use ($conn): bool {
            $ensure_sql_local = "
                SELECT COLUMN_NAME
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = 'users'
                  AND column_name = ?
            ";
            $st = $conn->prepare($ensure_sql_local);
            if (!$st) return false;
            $st->bind_param('s', $col);
            $st->execute();
            $st->store_result();
            $exists = $st->num_rows > 0;
            $st->close();
            return $exists;
        };

        if (!$ensure_account_status('account_status')) {
            $conn->query("ALTER TABLE users ADD COLUMN account_status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'");
        }

        $where[] = "account_status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }
}


$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try { $conn->query("ALTER TABLE users ADD COLUMN account_status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'"); } catch (Throwable $e) {}
try { $conn->query("ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL DEFAULT NULL"); } catch (Throwable $e) {}

$total_sql = "SELECT COUNT(*) AS total FROM users {$where_sql}";
$total = 0;
$total_stmt = $conn->prepare($total_sql);
if ($total_stmt) {
    if ($params) {
        $total_stmt->bind_param($types, ...$params);
    }
    $total_stmt->execute();
    $total_stmt->bind_result($total);
    $total_stmt->fetch();
    $total_stmt->close();
}

$total_pages = max(1, (int)ceil($total / $page_size));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $page_size;

$list_sql = "SELECT user_id, fullname, username, email, role, account_status,
                     created_at,
                     last_login_at
             FROM users
             {$where_sql}
             ORDER BY user_id DESC
             LIMIT ? OFFSET ?";

$ensure_sql = "
SELECT COLUMN_NAME
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = ?
";
$ensure = function(string $col) use ($conn, $ensure_sql): bool {
    $st = $conn->prepare($ensure_sql);
    if (!$st) return false;
    $st->bind_param('s', $col);
    $st->execute();
    $st->store_result();
    $exists = $st->num_rows > 0;
    $st->close();
    return $exists;
};

if (!$ensure('created_at')) {
    $conn->query("ALTER TABLE users ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
}
if (!$ensure('account_status')) {
    $conn->query("ALTER TABLE users ADD COLUMN account_status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'");
}
if (!$ensure('last_login_at')) {
    $conn->query("ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL DEFAULT NULL");
}

$stmt = $conn->prepare($list_sql);
if (!$stmt) {
    die('Database error (prepare users list).');
}

$bind_params = [];
if ($params) {
    $bind_params = $params;
}
$types2 = $types;
$types2 .= 'ii';
$bind_params[] = $page_size;
$bind_params[] = $offset;

$stmt->bind_param($types2, ...$bind_params);
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();

// ---- Modal data (edit) endpoints (use query params) ----
if (($_GET['get_user'] ?? '') === '1') {
    $uid = (int)($_GET['user_id'] ?? 0);
    $out = ['ok' => false];
    if ($uid > 0) {
        // Ensure account_status exists before selecting it (avoid fatal error)
        $ensure_account_status = function(string $col) use ($conn): bool {
            $ensure_sql_local = "
                SELECT COLUMN_NAME
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = 'users'
                  AND column_name = ?
            ";
            $st = $conn->prepare($ensure_sql_local);
            if (!$st) return false;
            $st->bind_param('s', $col);
            $st->execute();
            $st->store_result();
            $exists = $st->num_rows > 0;
            $st->close();
            return $exists;
        };

        if (!$ensure_account_status('account_status')) {
            $conn->query("ALTER TABLE users ADD COLUMN account_status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'");
        }

        $st = $conn->prepare("SELECT user_id, fullname, username, email, role, account_status FROM users WHERE user_id = ?");
        $st->bind_param('i', $uid);
        $st->execute();
        $res = $st->get_result();
        if ($u = $res->fetch_assoc()) {
            $out = ['ok' => true, 'user' => $u];
        }
        $st->close();
    }
    header('Content-Type: application/json');
    echo json_encode($out);
    exit();
}


// ---- Applicants count (needed for dashboard card #3) ----
$total_applicants = 0;
try {
    $app_stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM users WHERE role = 'Applicant'");
    if ($app_stmt) {
        $app_stmt->execute();
        $app_stmt->bind_result($total_applicants);
        $app_stmt->fetch();
        $app_stmt->close();
    }
} catch (Throwable $e) {
    $total_applicants = 0;
}

?>

<?php
// Centralized UI header/shell
include('includes/admin_user_management_header.php');
?>


            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card card-metric h-100">
                        <div class="card-body">
                            <div class="text-muted">Total Administrators</div>
                            <div class="fs-3 fw-bold"><?php echo (int)($counts['Admin'] ?? 0); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card card-metric h-100">
                        <div class="card-body">
                            <div class="text-muted">Total Consultants</div>
                            <div class="fs-3 fw-bold"><?php echo (int)($counts['Consultant'] ?? 0); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card card-metric h-100">
                        <div class="card-body">
                            <div class="text-muted">Total Job-Seekers</div>
                            <div class="fs-3 fw-bold"><?php echo (int)$total_applicants; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card card-metric h-100">
                        <div class="card-body">
                            <div class="text-muted">Total Users</div>
                            <div class="fs-3 fw-bold"><?php echo (int)$total; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page-shell">
                <form class="row g-2 align-items-end mb-3" method="get" action="admin_user_management.php">
                    <div class="col-12 col-md-6">
                        <label class="form-label mb-1">Search</label>
                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Username, name, or email">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1">Role</label>
                        <select class="form-select" name="role_filter">
                            <?php foreach (['all'=>'All Roles','Admin'=>'Administrator','Consultant'=>'Consultant'] as $k=>$label): ?>
                                <option value="<?php echo htmlspecialchars($k); ?>" <?php echo $role_filter===$k?'selected':''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1">Account Status</label>
                        <select class="form-select" name="status_filter">
                            <option value="all" <?php echo $status_filter==='all'?'selected':''; ?>>All</option>
                            <option value="Active" <?php echo $status_filter==='Active'?'selected':''; ?>>Active</option>
                            <option value="Inactive" <?php echo $status_filter==='Inactive'?'selected':''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2 justify-content-md-end">
                        <button type="submit" class="btn btn-primary"><i class='bx bx-search'></i> Search</button>
                        <a href="admin_user_management.php" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-muted">
                        Showing <?php echo count($users); ?> of <?php echo (int)$total; ?> users
                    </div>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class='bx bx-plus-circle'></i> Create User
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>User ID</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Email Address</th>
                                <th>Role</th>
                                <th>Account Status</th>
                                <th>Date Created</th>
                                <th>Last Login</th>
                                <th style="min-width: 220px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$users): ?>
                                <tr><td colspan="9" class="text-center text-muted py-4">No users found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?php echo (int)$u['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($u['fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td><span class="badge text-bg-info"><?php echo htmlspecialchars($u['role']); ?></span></td>
                                        <td>
                                            <?php $st = $u['account_status'] ?? 'Active'; ?>
                                            <span class="badge <?php echo $st==='Active'?'text-bg-success':'text-bg-secondary'; ?>"><?php echo htmlspecialchars($st); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($u['created_at'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($u['last_login_at'] ?? 'N/A'); ?></td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php $st = $u['account_status'] ?? 'Active'; ?>
                                                <?php if ($st === 'Active'): ?>
                                                    <form method="post" class="m-0" onsubmit="return confirm('Deactivate this account?')">
                                                        <input type="hidden" name="action" value="set_activation">
                                                        <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                                                        <input type="hidden" name="account_status" value="Inactive">
                                                        <button type="submit" class="btn btn-sm btn-warning">Deactivate</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="post" class="m-0" onsubmit="return confirm('Activate this account?')">
                                                        <input type="hidden" name="action" value="set_activation">
                                                        <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                                                        <input type="hidden" name="account_status" value="Active">
                                                        <button type="submit" class="btn btn-sm btn-success">Activate</button>
                                                    </form>
                                                <?php endif; ?>

                                                <form method="post" class="m-0" onsubmit="return confirm('Delete this user? This cannot be undone.')">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <nav class="mt-3" aria-label="User management pagination">
                    <ul class="pagination justify-content-center">
                        <?php
                            $q = [];
                            if ($search !== '') $q['search'] = $search;
                            if ($role_filter !== 'all') $q['role_filter'] = $role_filter;
                            if ($status_filter !== 'all') $q['status_filter'] = $status_filter;
                            $build = function($p) use ($q) {
                                $qs = $q;
                                $qs['page'] = $p;
                                return 'admin_user_management.php?' . http_build_query($qs);
                            };
                        ?>
                        <li class="page-item <?php echo $page<=1?'disabled':''; ?>">
                            <a class="page-link" href="<?php echo $page<=1?'#':$build($page-1); ?>">Prev</a>
                        </li>
                        <?php
                            $start = max(1, $page-2);
                            $end = min($total_pages, $page+2);
                            for ($p=$start;$p<=$end;$p++):
                        ?>
                            <li class="page-item <?php echo $p===$page?'active':''; ?>">
                                <a class="page-link" href="<?php echo $build($p); ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page>=$total_pages?'disabled':''; ?>">
                            <a class="page-link" href="<?php echo $page>=$total_pages?'#':$build($page+1); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="post" action="admin_user_management.php" id="createUserForm">
                    <input type="hidden" name="action" value="create_user">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class='bx bx-plus-circle'></i> Create New User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="fullname" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="createPassword" class="form-control" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleCreatePassword" aria-label="Show/Hide password">
                                        <i class='bx bx-hide'></i>
                                    </button>
                                </div>
                                <div class="form-text">Must be strong (uppercase, lowercase, number, special, min 8).</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" id="createConfirmPassword" class="form-control" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleCreateConfirmPassword" aria-label="Show/Hide confirm password">
                                        <i class='bx bx-hide'></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select" required>
                                    <option value="Admin">Administrator</option>
                                    <option value="Consultant">Consultant</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Status</label>
                                <select name="account_status" class="form-select" required>
                                    <option value="Active" selected>Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-3 mb-0">
                            Public registration remains restricted to Applicants only. Only Administrators can create Administrator/Consultant accounts.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success"><i class='bx bx-check'></i> Create User</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal (kept from your original file; actions UI not wired in this simplified re-render) -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="post" action="admin_user_management.php" id="editUserForm">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="editUserId">

                    <div class="modal-header">
                        <h5 class="modal-title"><i class='bx bx-edit'></i> Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="fullname" class="form-control" id="editFullname" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" id="editUsername" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" id="editEmail" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select" id="editRole" required>
                                    <option value="Admin">Administrator</option>
                                    <option value="Consultant">Consultant</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Status</label>
                                <select name="account_status" class="form-select" id="editAccountStatus" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary"><i class='bx bx-save'></i> Save Changes</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Password mismatch validation
        document.getElementById('createUserForm')?.addEventListener('submit', (e) => {
            const p = document.getElementById('createPassword');
            const c = document.getElementById('createConfirmPassword');
            if (p && c && p.value !== c.value) {
                e.preventDefault();
                alert('Passwords do not match.');
            }
        });

        // Show/Hide password toggles (eye icon)
        const togglePassword = (toggleBtnId, inputId) => {
            const btn = document.getElementById(toggleBtnId);
            const input = document.getElementById(inputId);
            if (!btn || !input) return;

            btn.addEventListener('click', () => {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.toggle('bx-hide', !isPassword);
                    icon.classList.toggle('bx-show', isPassword);
                }
            });
        };

        // Default icons are bx-hide; clicking swaps to bx-show and back.
        // (If your icon set doesn't include bx-show, you can change bx-show to bx-show-alt.)
        togglePassword('toggleCreatePassword', 'createPassword');
        togglePassword('toggleCreateConfirmPassword', 'createConfirmPassword');
    </script>
</body>
</html>

