<?php $area = $area ?? 'public'; ?>
<?php if ($area === 'admin'): ?>
    </main>
  </div>
</div>
<?php else: ?>
</main>
<footer class="site-foot">
  <div class="wrap">
    <span>&copy; <?= date('Y') ?> NSBM StudyCircle &middot; Faculty of Computing, NSBM Green University</span>
    <span class="muted">Built for module Web and Mobile Application Development, Batch 26.1 IFSL</span>
  </div>
</footer>
<?php endif; ?>
<script src="<?= h(url('assets/js/app.js')) ?>"></script>
</body>
</html>
