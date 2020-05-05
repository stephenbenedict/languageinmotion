<?php if ($page->parent()->uid() != 'library'): // Keep the page simple for readers ?>
  <footer class="wrapper">
    <nav>
      <a href="<?php echo page('support')->url() ?>" class="btn">Support</a>
      <a href="<?php echo page('about')->url() ?>" class="btn">About</a>
      <a href="<?php echo page('changelog')->url() ?>" class="btn">Changelog</a>
    </nav>
    &copy; <?php echo date('Y') ?> <?php echo $site->author()->html() ?>
  </footer>
<?php endif ?>
</div>

<?php echo js('assets/js/jquery.js') ?>
<script type="application/javascript" src="<?php echo $site->url() ?>/assets/js/fastclick.js">
<script type="text/javascript">
$(function() {
  FastClick.attach(document.body);
});
</script>
<?php if (isset($customJS)): ?>
  <?php foreach ($customJS as $script): ?>
    <?php echo js('assets/js/' . $script) ?>
  <?php endforeach ?>
<?php endif ?>
</body>
</html>
