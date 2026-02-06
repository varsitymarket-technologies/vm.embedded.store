            <header class="flex h-16 items-center justify-between bg-gray-800 px-6 border-b border-white/10">
                <button id="sidebarOpen" class="text-gray-400 hover:text-white md:hidden">
                    <i class="bi bi-list text-2xl"></i>
                </button>
                <div class="flex items-center gap-4 ml-auto">
                    <div class="relative group">
                        <button class="flex items-center gap-2 text-sm font-medium text-white focus:outline-none">
                            <div class="h-8 w-8 rounded-full bg-purple-600 flex items-center justify-center">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <span class="hidden md:block"><?php echo __USERNAME__; ?></span>
                        </button>
                    </div>
                </div>
            </header>
