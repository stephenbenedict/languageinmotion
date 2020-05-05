<nav>
  <?php if ($page->uid() == 'preview'): ?>
    <a href="<?php echo page('publish')->url() ?>">&larr;Back</a>
    <a href="<?php echo $page->siblings()->find('generate')->url() ?>">Generate&rarr;</a> 
  <?php elseif ($page->uid() == 'generate'): ?>
    <a href="<?php echo $page->siblings()->find('preview')->url() ?>">&larr;Back</a>
    <a href="<?php echo $page->siblings()->find('edit')->url() ?>">Edit&rarr;</a> 
  <?php elseif ($page->uid() == 'edit'): ?>
    <a href="<?php echo $page->siblings()->find('generate')->url() ?>">&larr;Back</a>
    <a href="<?php echo $page->siblings()->find('finalize')->url() ?>">Finalize&rarr;</a>
  <?php elseif ($page->uid() == 'finalize'): ?>
    <a href="<?php echo $page->siblings()->find('edit')->url() ?>">&larr;Back</a>
    <a href="<?php echo $page->siblings()->find('updateboilerplate')->url() ?>">Update Boilerplate&rarr;</a>    
  <?php else: ?>
    <a href="<?php echo $page->siblings()->find('edit')->url() ?>">&larr;Back</a>
  <?php endif ?>
</nav>
