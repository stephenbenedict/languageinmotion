<ul class="breadcrumb">
  <li <?php e($page->uid() == 'preview', "class='active'") ?>>Preview</li>
  &rarr;
  <li <?php e($page->uid() == 'generate', "class='active'") ?>>Generate</li>
  &rarr;
  <li <?php e($page->uid() == 'edit', "class='active'") ?>>Edit</li>
  &rarr;
  <li <?php e($page->uid() == 'finalize', "class='active'") ?>>Finalize</li>
  &rarr;
  <li <?php e($page->uid() == 'updateboilerplate', "class='active'") ?>>Update Boilerplate</li>
</ul>
