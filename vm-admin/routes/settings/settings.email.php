
                    <div>
                        <button onclick="window.location.href='?tab=general'"
                            class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                            Back To Settings
                        </button>
                    </div>
                    <br><br>

                    <form method="POST" id="emailForm">
                        <input type="hidden" name="action" value="save_email_config">
                        <div class="v-card animate-slide-up">
                            <div class="v-card-header flex justify-between items-center">
                                <div>
                                    <h2 class="text-xl font-bold text-white">Email Configuration</h2>
                                    <p class="text-sm text-gray-400 mt-2">Securely store SMTP and notification templates.
                                    </p>
                                </div>
                            </div>
                            <div class="v-card-body space-y-12">
                                <!-- SMTP Section -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="space-y-3">
                                        <label class="text-xs font-black uppercase tracking-widest text-gray-500">SMTP
                                            Host</label>
                                        <input type="text" name="email[host]"
                                            value="<?= htmlspecialchars($email_current['host']) ?>"
                                            placeholder="smtp.gmail.com"
                                            class="w-full bg-[#080808] border border-white/10 rounded-xl px-4 py-3.5 focus:border-purple-500 outline-none transition-all">
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-xs font-black uppercase tracking-widest text-gray-500">SMTP
                                            Port</label>
                                        <input type="text" name="email[port]"
                                            value="<?= htmlspecialchars($email_current['port']) ?>"
                                            class="w-full bg-[#080808] border border-white/10 rounded-xl px-4 py-3.5 focus:border-purple-500 outline-none transition-all">
                                    </div>
                                    <div class="space-y-3">
                                        <label
                                            class="text-xs font-black uppercase tracking-widest text-gray-500">Username</label>
                                        <input type="text" name="email[user]"
                                            value="<?= htmlspecialchars($email_current['user']) ?>"
                                            class="w-full bg-[#080808] border border-white/10 rounded-xl px-4 py-3.5 focus:border-purple-500 outline-none transition-all">
                                    </div>
                                    <div class="space-y-3">
                                        <label
                                            class="text-xs font-black uppercase tracking-widest text-gray-500">Password</label>
                                        <div class="relative group">
                                            <input type="password" name="email[pass]"
                                                value="<?= htmlspecialchars($email_current['pass']) ?>"
                                                class="w-full bg-[#080808] border border-white/10 rounded-xl px-4 py-3.5 focus:border-purple-500 outline-none transition-all">
                                            <i
                                                class="bi bi-eye-slash absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 group-hover:text-gray-400 cursor-pointer"></i>
                                        </div>
                                    </div>
                                </div>

                                <!-- Template Editor -->
                                <div class="space-y-4">
                                    <div class="flex justify-between items-end">
                                        <div class="space-y-1">
                                            <label class="text-xs font-black uppercase tracking-widest text-gray-500">System
                                                Notification Template</label>
                                            <p class="text-[10px] text-gray-600">Supports <code
                                                    class="text-purple-400">{{name}}</code> and <code
                                                    class="text-purple-400">{{message}}</code> tags.</p>
                                        </div>
                                        <button type="button" onclick="togglePreview()"
                                            class="px-4 py-1.5 bg-purple-600/10 text-purple-400 rounded-full text-[10px] font-black uppercase tracking-widest hover:bg-purple-600 hover:text-white transition-all">
                                            <i class="bi bi-eye-fill mr-1"></i> Preview Template
                                        </button>
                                    </div>
                                    <textarea name="email[template]" id="emailTemplate" rows="12"
                                        class="w-full bg-[#020202] border border-white/10 rounded-2xl px-6 py-6 font-mono text-xs text-purple-300 outline-none focus:border-purple-500 leading-relaxed shadow-lg"><?= htmlspecialchars($email_current['template']) ?></textarea>
                                </div>
                            </div>
                            <div class="v-card-footer">
                                <button type="submit"
                                    class="bg-purple-600 text-white px-8 py-2.5 rounded-full text-sm font-black hover:bg-purple-500 transition-all shadow-xl shadow-purple-900/40">
                                    Save Configuration
                                </button>
                            </div>
                        </div>
                    </form>


                    <!-- Preview Display -->
                    <div id="previewContainer" class="hidden animate-slide-up mt-8">
                        <div class="v-card border-purple-500/30 overflow-hidden">
                            <div class="v-card-header bg-purple-600/5 flex justify-between items-center py-4">
                                <h2 class="text-xs font-black uppercase tracking-[0.3em] text-purple-400">Live Render</h2>
                                <button onclick="togglePreview()"
                                    class="text-gray-500 hover:text-white transition-colors"><i
                                        class="bi bi-x-lg"></i></button>
                            </div>
                            <div class="v-card-body bg-[#fcfcfc] p-0 shadow-inner">
                                <iframe id="previewFrame" class="w-full h-[600px] border-none shadow-2xl"></iframe>
                            </div>
                        </div>
                    </div>

                    <script>
                        function togglePreview() {
                            const container = document.getElementById('previewContainer');
                            const isHidden = container.classList.contains('hidden');

                            if (isHidden) {
                                container.classList.remove('hidden');
                                updatePreview();
                                setTimeout(() => container.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
                            } else {
                                container.classList.add('hidden');
                            }
                        }

                        function updatePreview() {
                            const template = document.getElementById('emailTemplate').value;
                            const frame = document.getElementById('previewFrame');

                            let rendered = template
                                .replace(/{{name}}/g, 'Valued Customer')
                                .replace(/{{message}}/g, 'This is a sample encrypted notification sent from your Store Admin. The styling here will match how your customers see automated emails.')
                                .replace(/{{link}}/g, '#');

                            const doc = frame.contentDocument || frame.contentWindow.document;
                            doc.open();
                            doc.write(rendered);
                            doc.close();
                        }
                    </script>

                    <br><br>


