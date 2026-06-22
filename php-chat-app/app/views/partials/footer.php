	<?php if (!empty($extra_scripts) && is_array($extra_scripts)): ?>
		<?php foreach ($extra_scripts as $es): ?>
			<script src="<?= htmlspecialchars($es, ENT_QUOTES, 'UTF-8'); ?>"></script>
		<?php endforeach; ?>
	<?php elseif (!empty($extra_scripts) && is_string($extra_scripts)): ?>
		<script src="<?= htmlspecialchars($extra_scripts, ENT_QUOTES, 'UTF-8'); ?>"></script>
	<?php endif; ?>
</body>
</html>
