        <header>
            <div class="logo" style="display: flex; align-items: center;">
                <img src="/assets/favicon.png" style="max-width: 4rem; margin: auto; width: 100%;">
                <p style="display: flex;align-items: flex-start;flex-direction: column;font-size: 15px;"> Embedded <span class="text-purple" style="">Store</span></p>
            </div>

            <div class="user-profile">
                <span id="username-display"><?php echo __USERNAME__; ?></span>
                <button class="btn-small" onclick="handleLogout()">Logout</button>
            </div>
        </header>
