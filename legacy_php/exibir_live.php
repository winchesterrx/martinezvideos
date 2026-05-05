<?php
session_start();
if (isset($_SESSION['live_link']) && !empty($_SESSION['live_link'])): ?>
    <div id="livePlayerModal">
        <div class="live-content">
            <span class="close-live" onclick="closeLivePlayer()">&times;</span>
            <iframe width="560" height="315" src="<?= htmlspecialchars($_SESSION['live_link']) ?>" frameborder="0" allowfullscreen></iframe>
        </div>
    </div>
<?php endif; ?>
