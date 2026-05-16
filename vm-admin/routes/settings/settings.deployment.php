
                    <div>
                        <button onclick="window.location.href='?tab=general'"
                            class="bg-white text-black px-8 py-2.5 rounded-full text-sm font-black hover:bg-gray-200 transition-all transform hover:scale-105 active:scale-95 shadow-xl">
                            Back To Settings
                        </button>
                    </div>
                    <br><br>

                    <div class="v-card animate-slide-up">
                        <div class="v-card-header">
                            <h2 class="text-xl font-bold text-white">Advanced Deployment</h2>
                            <p class="text-sm text-gray-400 mt-2">Connect your source code for automated delivery cycles.
                            </p>
                        </div>
                        <div class="v-card-body text-center py-20 px-8">
                            <?php if (isset($_SESSION['github_token'])): ?>
                                <div class="v-card animate-slide-up">
                                    <div class="v-card-body text-center py-20 px-8">
                                        <div
                                            class="w-24 h-24 bg-white/5 rounded-3xl flex items-center justify-center mx-auto mb-8 transform rotate-3 hover:rotate-0 transition-transform duration-500">
                                            <i class="bi bi-github text-5xl text-gray-300"></i>
                                        </div>
                                        <h3 class="text-2xl font-black text-white mb-4">Store Deployment Ready</h3>
                                        <p class="text-gray-500 text-sm max-w-sm mx-auto mb-10 leading-relaxed font-medium">
                                            Your store is now connected to your GitHub account. You can now proceed to deploy
                                            your store to GitHub.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div
                                    class="w-24 h-24 bg-white/5 rounded-3xl flex items-center justify-center mx-auto mb-8 transform rotate-3 hover:rotate-0 transition-transform duration-500">
                                    <i class="bi bi-github text-5xl text-gray-300"></i>
                                </div>
                                <h3 class="text-2xl font-black text-white mb-4">Connect GitHub Repository</h3>
                                <p class="text-gray-500 text-sm max-w-sm mx-auto mb-10 leading-relaxed font-medium">Authorize
                                    Varsity Market to automate your builds and push production updates directly to your hosting
                                    provider.</p>

                                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                                    <a onclick="window.location.href=`https://github.com/login/oauth/authorize?client_id=<?php echo $_SERVER['__GITHUB_APK_CLIENT__']; ?>`"
                                        class="inline-flex items-center gap-3 bg-[#24292e] hover:bg-black text-white px-8 py-3.5 rounded-full transition-all font-black text-sm shadow-xl shadow-black/40 group">
                                        <i class="bi bi-plug-fill text-lg group-hover:rotate-45 transition-transform"></i>
                                        Authorize GitHub
                                    </a>
                                    <a href="#"
                                        class="text-xs font-black uppercase tracking-widest text-gray-500 hover:text-white transition-colors">Setup
                                        Guide &rarr;</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>


