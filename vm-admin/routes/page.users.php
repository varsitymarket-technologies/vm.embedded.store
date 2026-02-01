<?php
$db = __DB_MODULE__;

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        $db->query($sql, [$username, $email, $hashed_password, $role]);
        
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'update_user') {
        $id = $_POST['id'] ?? 0;
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
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
$users = $db->query("SELECT * FROM users ORDER BY id DESC");
?>
        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <header class="flex h-16 items-center justify-between bg-gray-800 px-6 border-b border-white/10">
                <button id="sidebarOpen" class="text-gray-400 hover:text-white md:hidden">
                    <i class="bi bi-list text-2xl"></i>
                </button>
                <div class="flex items-center gap-4 ml-auto">
                    <button class="relative text-gray-400 hover:text-white">
                        <i class="bi bi-bell text-xl"></i>
                        <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-purple-500"></span>
                    </button>
                    <div class="relative group">
                        <button class="flex items-center gap-2 text-sm font-medium text-white focus:outline-none">
                            <div class="h-8 w-8 rounded-full bg-purple-600 flex items-center justify-center">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <span class="hidden md:block">Admin</span>
                            <i class="bi bi-chevron-down text-xs text-gray-400"></i>
                        </button>
                        <!-- Dropdown -->
                        <div class="absolute right-0 mt-2 w-48 origin-top-right rounded-md bg-gray-800 py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none hidden group-hover:block border border-white/10">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Profile</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Settings</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Sign out</a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-gray-900 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold">User Management</h2>
                    <button onclick="openModal('add')" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                        <i class="bi bi-person-plus-fill"></i> Add User
                    </button>
                </div>

                <!-- Users Table -->
                <div class="rounded-xl bg-gray-800 border border-white/5 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-400">
                            <thead class="bg-gray-700/50 text-xs uppercase text-gray-300">
                                <tr>
                                    <th scope="col" class="px-6 py-3">ID</th>
                                    <th scope="col" class="px-6 py-3">User</th>
                                    <th scope="col" class="px-6 py-3">Role</th>
                                    <th scope="col" class="px-6 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <i class="bi bi-people text-4xl mb-2"></i>
                                                <p>No users found. Click "Add User" to create one.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-gray-700/30 transition-colors">
                                        <td class="px-6 py-4 font-medium text-white">#<?php echo $user['id']; ?></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="h-10 w-10 rounded-full bg-gray-700 flex items-center justify-center text-gray-400">
                                                    <i class="bi bi-person-fill text-lg"></i>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-white"><?php echo htmlspecialchars($user['username']); ?></div>
                                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo ($user['role'] ?? 'user') === 'admin' ? 'bg-purple-500/10 text-purple-500' : 'bg-blue-500/10 text-blue-500'; ?>">
                                                <?php echo ucfirst($user['role'] ?? 'user'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex gap-3">
                                                <button onclick='openModal("edit", <?php echo json_encode($user); ?>)' class="text-blue-400 hover:text-blue-300 transition-colors" title="Edit">
                                                    <i class="bi bi-pencil-square text-lg"></i>
                                                </button>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');" class="inline">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="text-red-400 hover:text-red-300 transition-colors" title="Delete">
                                                        <i class="bi bi-trash text-lg"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>

        <!-- Modal -->
        <div id="userModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="closeModal()"></div>

                <div class="relative inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-white/10">
                    <form method="POST" id="userForm">
                        <input type="hidden" name="action" id="formAction" value="add_user">
                        <input type="hidden" name="id" id="userId">
                        
                        <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex justify-between items-center mb-5">
                                <h3 class="text-lg leading-6 font-medium text-white" id="modalTitle">Add New User</h3>
                                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-white">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Username</label>
                                    <input type="text" name="username" id="userUsername" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Email Address</label>
                                    <input type="email" name="email" id="userEmail" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Password <span id="passwordHint" class="text-xs text-gray-500 hidden">(Leave blank to keep current)</span></label>
                                    <input type="password" name="password" id="userPassword" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Role</label>
                                    <select name="role" id="userRole" class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors">
                                        <option value="user">User</option>
                                        <option value="admin">Admin</option>
                                        <option value="editor">Editor</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-700/30 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-white/5">
                            <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                Save User
                            </button>
                            <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-600 shadow-sm px-4 py-2 bg-transparent text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function openModal(mode, user = null) {
            const modal = document.getElementById('userModal');
            const form = document.getElementById('userForm');
            const title = document.getElementById('modalTitle');
            const action = document.getElementById('formAction');
            const passwordHint = document.getElementById('passwordHint');
            const passwordInput = document.getElementById('userPassword');
            
            modal.classList.remove('hidden');
            
            if (mode === 'edit' && user) {
                title.textContent = 'Edit User';
                action.value = 'update_user';
                passwordHint.classList.remove('hidden');
                passwordInput.removeAttribute('required');
                
                document.getElementById('userId').value = user.id;
                document.getElementById('userUsername').value = user.username;
                document.getElementById('userEmail').value = user.email;
                document.getElementById('userRole').value = user.role || 'user';
                document.getElementById('userPassword').value = '';
            } else {
                title.textContent = 'Add New User';
                action.value = 'add_user';
                passwordHint.classList.add('hidden');
                passwordInput.setAttribute('required', 'required');
                
                form.reset();
                document.getElementById('userId').value = '';
            }
        }

        function closeModal() {
            document.getElementById('userModal').classList.add('hidden');
        }
        </script>