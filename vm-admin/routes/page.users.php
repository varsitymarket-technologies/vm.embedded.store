<?php
$db = initiate_web_database();

// Ensure users table exists
$db->query("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL, email TEXT, password TEXT, role TEXT DEFAULT 'user', created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if (!empty($username) && !empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
            $db->query($sql, [$username, $email, $hashed_password, $role]);
        }

        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'update_user') {
        $id = $_POST['id'] ?? 0;
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $password = $_POST['password'] ?? '';

        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?";
            $db->query($sql, [$username, $email, $hashed_password, $role, $id]);
        } else {
            $sql = "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?";
            $db->query($sql, [$username, $email, $role, $id]);
        }

        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'delete_user') {
        $id = $_POST['id'] ?? 0;
        $sql = "DELETE FROM users WHERE id = ?";
        $db->query($sql, [$id]);

        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    }
}

// Fetch Users
$users = $db->query("SELECT * FROM users ORDER BY id DESC") ?: [];

// Compute stats
$totalUsers = count($users);
$totalAdmins = 0;
$totalEditors = 0;
$totalRegular = 0;
foreach ($users as $u) {
    $r = $u['role'] ?? 'user';
    if ($r === 'admin') $totalAdmins++;
    elseif ($r === 'editor') $totalEditors++;
    else $totalRegular++;
}
?>
        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <?php @include_once "header.php"; ?>

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-6">

                <!-- Page Title + Add Button -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-white">User Management</h2>
                        <p class="text-sm text-gray-400 mt-1">Manage your store users, roles, and permissions</p>
                    </div>
                    <button onclick="openModal('add')" class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 rounded-lg flex items-center gap-2 transition-colors font-medium text-sm shadow-lg shadow-purple-600/20">
                        <i class="bi bi-person-plus-fill"></i> Add User
                    </button>
                </div>

                <!-- Stat Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!-- Total Users -->
                    <div class="bg-gray-800 rounded-xl border border-white/5 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <div class="h-10 w-10 rounded-lg bg-purple-600/20 flex items-center justify-center">
                                <i class="bi bi-people-fill text-lg text-purple-400"></i>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $totalUsers; ?></p>
                        <p class="text-xs text-gray-400 mt-1">Total Users</p>
                    </div>
                    <!-- Admins -->
                    <div class="bg-gray-800 rounded-xl border border-white/5 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <div class="h-10 w-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                                <i class="bi bi-shield-lock-fill text-lg text-purple-400"></i>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $totalAdmins; ?></p>
                        <p class="text-xs text-gray-400 mt-1">Admins</p>
                    </div>
                    <!-- Editors -->
                    <div class="bg-gray-800 rounded-xl border border-white/5 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <div class="h-10 w-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                <i class="bi bi-pencil-fill text-lg text-blue-400"></i>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $totalEditors; ?></p>
                        <p class="text-xs text-gray-400 mt-1">Editors</p>
                    </div>
                    <!-- Regular Users -->
                    <div class="bg-gray-800 rounded-xl border border-white/5 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <div class="h-10 w-10 rounded-lg bg-gray-600/30 flex items-center justify-center">
                                <i class="bi bi-person-fill text-lg text-gray-400"></i>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white"><?php echo $totalRegular; ?></p>
                        <p class="text-xs text-gray-400 mt-1">Regular Users</p>
                    </div>
                </div>

                <!-- Search + Filter Bar -->
                <div class="bg-gray-800 rounded-xl border border-white/5 p-4 mb-6">
                    <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                        <!-- Search -->
                        <div class="relative w-full md:w-80">
                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                            <input type="text" id="searchInput" placeholder="Search by name or email..." oninput="filterUsers()"
                                class="w-full bg-gray-700 border border-white/5 rounded-lg pl-10 pr-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                        </div>
                        <!-- Role Filter Tabs -->
                        <div class="flex items-center gap-1 bg-gray-700/50 rounded-lg p-1">
                            <button onclick="setRoleFilter('all')" data-filter="all" class="role-tab active-tab px-3.5 py-1.5 rounded-md text-xs font-medium transition-colors">All</button>
                            <button onclick="setRoleFilter('admin')" data-filter="admin" class="role-tab px-3.5 py-1.5 rounded-md text-xs font-medium transition-colors">Admin</button>
                            <button onclick="setRoleFilter('editor')" data-filter="editor" class="role-tab px-3.5 py-1.5 rounded-md text-xs font-medium transition-colors">Editor</button>
                            <button onclick="setRoleFilter('user')" data-filter="user" class="role-tab px-3.5 py-1.5 rounded-md text-xs font-medium transition-colors">User</button>
                        </div>
                    </div>
                </div>

                <!-- Users List -->
                <?php if (empty($users)): ?>
                    <!-- Empty State -->
                    <div class="bg-gray-800 rounded-xl border border-white/5 p-16 text-center">
                        <div class="inline-flex items-center justify-center h-20 w-20 rounded-full bg-gray-700/50 mb-5">
                            <i class="bi bi-people text-4xl text-gray-500"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">No users yet</h3>
                        <p class="text-sm text-gray-400 mb-6 max-w-sm mx-auto">Get started by adding your first user. You can assign roles and manage permissions for each user.</p>
                        <button onclick="openModal('add')" class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 rounded-lg inline-flex items-center gap-2 transition-colors font-medium text-sm">
                            <i class="bi bi-person-plus-fill"></i> Add Your First User
                        </button>
                    </div>
                <?php else: ?>
                    <!-- No Results State (shown via JS when filters match nothing) -->
                    <div id="noResults" class="bg-gray-800 rounded-xl border border-white/5 p-12 text-center hidden">
                        <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-gray-700/50 mb-4">
                            <i class="bi bi-search text-2xl text-gray-500"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-1">No matches found</h3>
                        <p class="text-sm text-gray-400">Try adjusting your search or filter criteria.</p>
                    </div>

                    <!-- User Cards (Mobile-friendly) + Table (Desktop) -->
                    <div class="bg-gray-800 rounded-xl border border-white/5 overflow-hidden" id="usersContainer">

                        <!-- Desktop Table -->
                        <div class="hidden md:block">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm text-gray-400">
                                    <thead class="bg-gray-700/40 text-xs uppercase text-gray-400 tracking-wider">
                                        <tr>
                                            <th scope="col" class="px-6 py-4">User</th>
                                            <th scope="col" class="px-6 py-4">Role</th>
                                            <th scope="col" class="px-6 py-4">Joined</th>
                                            <th scope="col" class="px-6 py-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-white/5">
                                        <?php foreach ($users as $user):
                                            $role = $user['role'] ?? 'user';
                                            $initial = strtoupper(substr($user['username'] ?? '?', 0, 1));
                                            $avatarColors = [
                                                'admin' => 'bg-purple-600 text-purple-100',
                                                'editor' => 'bg-blue-600 text-blue-100',
                                                'user' => 'bg-gray-600 text-gray-200',
                                            ];
                                            $badgeColors = [
                                                'admin' => 'bg-purple-500/15 text-purple-400 ring-1 ring-purple-500/20',
                                                'editor' => 'bg-blue-500/15 text-blue-400 ring-1 ring-blue-500/20',
                                                'user' => 'bg-gray-500/15 text-gray-400 ring-1 ring-gray-500/20',
                                            ];
                                            $avatarClass = $avatarColors[$role] ?? $avatarColors['user'];
                                            $badgeClass = $badgeColors[$role] ?? $badgeColors['user'];
                                            $createdAt = !empty($user['created_at']) ? date('M j, Y', strtotime($user['created_at'])) : 'N/A';
                                        ?>
                                        <tr class="user-row hover:bg-gray-700/30 transition-colors" data-username="<?php echo htmlspecialchars(strtolower($user['username'] ?? '')); ?>" data-email="<?php echo htmlspecialchars(strtolower($user['email'] ?? '')); ?>" data-role="<?php echo htmlspecialchars($role); ?>">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="h-10 w-10 rounded-full <?php echo $avatarClass; ?> flex items-center justify-center font-semibold text-sm flex-shrink-0">
                                                        <?php echo $initial; ?>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="font-medium text-white truncate"><?php echo htmlspecialchars($user['username']); ?></div>
                                                        <div class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?php echo $badgeClass; ?>">
                                                    <?php echo ucfirst($role); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-gray-400 text-sm"><?php echo $createdAt; ?></td>
                                            <td class="px-6 py-4">
                                                <div class="flex gap-2 justify-end">
                                                    <button onclick='openModal("edit", <?php echo json_encode($user); ?>)' class="h-8 w-8 rounded-lg bg-gray-700/50 hover:bg-gray-700 flex items-center justify-center text-blue-400 hover:text-blue-300 transition-colors" title="Edit user">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <button onclick='confirmDelete(<?php echo (int)$user["id"]; ?>, "<?php echo htmlspecialchars(addslashes($user["username"])); ?>")' class="h-8 w-8 rounded-lg bg-gray-700/50 hover:bg-red-600/20 flex items-center justify-center text-red-400 hover:text-red-300 transition-colors" title="Delete user">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="md:hidden divide-y divide-white/5">
                            <?php foreach ($users as $user):
                                $role = $user['role'] ?? 'user';
                                $initial = strtoupper(substr($user['username'] ?? '?', 0, 1));
                                $avatarColors = [
                                    'admin' => 'bg-purple-600 text-purple-100',
                                    'editor' => 'bg-blue-600 text-blue-100',
                                    'user' => 'bg-gray-600 text-gray-200',
                                ];
                                $badgeColors = [
                                    'admin' => 'bg-purple-500/15 text-purple-400 ring-1 ring-purple-500/20',
                                    'editor' => 'bg-blue-500/15 text-blue-400 ring-1 ring-blue-500/20',
                                    'user' => 'bg-gray-500/15 text-gray-400 ring-1 ring-gray-500/20',
                                ];
                                $avatarClass = $avatarColors[$role] ?? $avatarColors['user'];
                                $badgeClass = $badgeColors[$role] ?? $badgeColors['user'];
                                $createdAt = !empty($user['created_at']) ? date('M j, Y', strtotime($user['created_at'])) : 'N/A';
                            ?>
                            <div class="user-card p-4" data-username="<?php echo htmlspecialchars(strtolower($user['username'] ?? '')); ?>" data-email="<?php echo htmlspecialchars(strtolower($user['email'] ?? '')); ?>" data-role="<?php echo htmlspecialchars($role); ?>">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="h-11 w-11 rounded-full <?php echo $avatarClass; ?> flex items-center justify-center font-semibold text-sm flex-shrink-0">
                                            <?php echo $initial; ?>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-medium text-white truncate"><?php echo htmlspecialchars($user['username']); ?></div>
                                            <div class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium flex-shrink-0 <?php echo $badgeClass; ?>">
                                        <?php echo ucfirst($role); ?>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between mt-3 pt-3 border-t border-white/5">
                                    <span class="text-xs text-gray-500"><i class="bi bi-calendar3 mr-1"></i> <?php echo $createdAt; ?></span>
                                    <div class="flex gap-2">
                                        <button onclick='openModal("edit", <?php echo json_encode($user); ?>)' class="h-8 w-8 rounded-lg bg-gray-700/50 hover:bg-gray-700 flex items-center justify-center text-blue-400 hover:text-blue-300 transition-colors" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button onclick='confirmDelete(<?php echo (int)$user["id"]; ?>, "<?php echo htmlspecialchars(addslashes($user["username"])); ?>")' class="h-8 w-8 rounded-lg bg-gray-700/50 hover:bg-red-600/20 flex items-center justify-center text-red-400 hover:text-red-300 transition-colors" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>

        <!-- Add/Edit User Modal -->
        <div id="userModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
                <div class="relative w-full max-w-md bg-gray-800 rounded-xl shadow-2xl border border-white/10 transform transition-all">
                    <form method="POST" id="userForm">
                        <input type="hidden" name="action" id="formAction" value="add_user">
                        <input type="hidden" name="id" id="userId">

                        <!-- Modal Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-white/5">
                            <div>
                                <h3 class="text-lg font-semibold text-white" id="modalTitle">Add New User</h3>
                                <p class="text-xs text-gray-400 mt-0.5" id="modalSubtitle">Fill in the details to create a new user</p>
                            </div>
                            <button type="button" onclick="closeModal()" class="h-8 w-8 rounded-lg hover:bg-gray-700 flex items-center justify-center text-gray-400 hover:text-white transition-colors">
                                <i class="bi bi-x-lg text-sm"></i>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="px-6 py-5 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1.5">Username</label>
                                <input type="text" name="username" id="userUsername" required
                                    class="w-full bg-gray-700 border border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors"
                                    placeholder="Enter username">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1.5">Email Address</label>
                                <input type="email" name="email" id="userEmail" required
                                    class="w-full bg-gray-700 border border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors"
                                    placeholder="user@example.com">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1.5">
                                    Password
                                    <span id="passwordHint" class="text-xs text-gray-500 font-normal hidden ml-1">(leave blank to keep current)</span>
                                </label>
                                <div class="relative">
                                    <input type="password" name="password" id="userPassword"
                                        class="w-full bg-gray-700 border border-white/10 rounded-lg px-3.5 py-2.5 pr-10 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors"
                                        placeholder="Enter password">
                                    <button type="button" onclick="togglePassword()" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-300 transition-colors p-0.5" tabindex="-1">
                                        <i class="bi bi-eye" id="passwordToggleIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1.5">Role</label>
                                <select name="role" id="userRole"
                                    class="w-full bg-gray-700 border border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                    <option value="user">User</option>
                                    <option value="editor">Editor</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-white/5 bg-gray-700/20">
                            <button type="button" onclick="closeModal()" class="px-4 py-2.5 rounded-lg border border-white/10 text-sm font-medium text-gray-300 hover:text-white hover:bg-gray-700 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-sm font-medium text-white transition-colors shadow-lg shadow-purple-600/20">
                                <span id="submitLabel">Create User</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeDeleteModal()"></div>
                <div class="relative w-full max-w-sm bg-gray-800 rounded-xl shadow-2xl border border-white/10">
                    <div class="p-6 text-center">
                        <div class="inline-flex items-center justify-center h-14 w-14 rounded-full bg-red-500/15 mb-4">
                            <i class="bi bi-exclamation-triangle-fill text-2xl text-red-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-1">Delete User</h3>
                        <p class="text-sm text-gray-400 mb-6">Are you sure you want to delete <strong id="deleteUserName" class="text-white"></strong>? This action cannot be undone.</p>
                        <div class="flex gap-3">
                            <button onclick="closeDeleteModal()" class="flex-1 px-4 py-2.5 rounded-lg border border-white/10 text-sm font-medium text-gray-300 hover:text-white hover:bg-gray-700 transition-colors">
                                Cancel
                            </button>
                            <form method="POST" id="deleteForm" class="flex-1">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="id" id="deleteUserId">
                                <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-sm font-medium text-white transition-colors">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .role-tab {
                color: #9ca3af;
            }
            .role-tab:hover {
                color: #d1d5db;
            }
            .role-tab.active-tab {
                background-color: rgba(147, 51, 234, 0.2);
                color: #c084fc;
            }
        </style>

        <script>
        let currentRoleFilter = 'all';

        function openModal(mode, user = null) {
            const modal = document.getElementById('userModal');
            const form = document.getElementById('userForm');
            const title = document.getElementById('modalTitle');
            const subtitle = document.getElementById('modalSubtitle');
            const action = document.getElementById('formAction');
            const passwordHint = document.getElementById('passwordHint');
            const passwordInput = document.getElementById('userPassword');
            const submitLabel = document.getElementById('submitLabel');

            // Reset password visibility
            passwordInput.type = 'password';
            document.getElementById('passwordToggleIcon').className = 'bi bi-eye';

            modal.classList.remove('hidden');

            if (mode === 'edit' && user) {
                title.textContent = 'Edit User';
                subtitle.textContent = 'Update the user details below';
                submitLabel.textContent = 'Save Changes';
                action.value = 'update_user';
                passwordHint.classList.remove('hidden');
                passwordInput.removeAttribute('required');
                passwordInput.placeholder = 'Leave blank to keep current';

                document.getElementById('userId').value = user.id;
                document.getElementById('userUsername').value = user.username || '';
                document.getElementById('userEmail').value = user.email || '';
                document.getElementById('userRole').value = user.role || 'user';
                document.getElementById('userPassword').value = '';
            } else {
                title.textContent = 'Add New User';
                subtitle.textContent = 'Fill in the details to create a new user';
                submitLabel.textContent = 'Create User';
                action.value = 'add_user';
                passwordHint.classList.add('hidden');
                passwordInput.setAttribute('required', 'required');
                passwordInput.placeholder = 'Enter password';

                form.reset();
                document.getElementById('userId').value = '';
            }
        }

        function closeModal() {
            document.getElementById('userModal').classList.add('hidden');
        }

        function confirmDelete(id, username) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteUserName').textContent = username;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        function togglePassword() {
            const input = document.getElementById('userPassword');
            const icon = document.getElementById('passwordToggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }

        function setRoleFilter(role) {
            currentRoleFilter = role;
            document.querySelectorAll('.role-tab').forEach(function(tab) {
                tab.classList.remove('active-tab');
                if (tab.getAttribute('data-filter') === role) {
                    tab.classList.add('active-tab');
                }
            });
            filterUsers();
        }

        function filterUsers() {
            const query = (document.getElementById('searchInput').value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.user-row');
            const cards = document.querySelectorAll('.user-card');
            let visibleCount = 0;

            function shouldShow(el) {
                const username = el.getAttribute('data-username') || '';
                const email = el.getAttribute('data-email') || '';
                const role = el.getAttribute('data-role') || '';

                const matchesSearch = !query || username.indexOf(query) !== -1 || email.indexOf(query) !== -1;
                const matchesRole = currentRoleFilter === 'all' || role === currentRoleFilter;

                return matchesSearch && matchesRole;
            }

            rows.forEach(function(row) {
                const visible = shouldShow(row);
                row.style.display = visible ? '' : 'none';
                if (visible) visibleCount++;
            });

            cards.forEach(function(card) {
                const visible = shouldShow(card);
                card.style.display = visible ? '' : 'none';
            });

            var noResults = document.getElementById('noResults');
            var container = document.getElementById('usersContainer');
            if (noResults && container) {
                if (visibleCount === 0) {
                    noResults.classList.remove('hidden');
                    container.classList.add('hidden');
                } else {
                    noResults.classList.add('hidden');
                    container.classList.remove('hidden');
                }
            }
        }

        // Close modals on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeDeleteModal();
            }
        });
        </script>
