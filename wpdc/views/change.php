<form name="login" method="post" action="<?php echo $form_path; ?>">
  <div class="col-md-6">

    <div class="panel">
      <div class="heading">
        <h2 class="title engrave">WordPress Tables</h2>
      </div>
      <div class="body">
        <ul style="font-size:0.8em">
          <?php foreach($tables as $table => $table_info): ?>
          <li>
            <label><input type="checkbox" name="tables[<?php echo $table ?>]" value="1" checked /> <code><?php echo $table ?> - (<?php echo $table_info["rows"] ?> Record<?php echo $table_info["rows"] > 1 ? "s" : "" ?>)</code></label>
            <ul>
              <?php foreach($table_info["description"] as $column => $column_info): ?>
              <?php if(!$column_info["is_stringish"]) continue; ?>
              <li><label><input type="checkbox" name="tables[<?php echo $table ?>][columns][<?php echo $column ?>]" value="1" checked /> <code><?php echo $column ?></code></label></li>
              <?php endforeach ?>
            </ul>
          </li>
          <?php endforeach ?>
        </ul>
      </div>
    </div>

  </div>

  <div class="col-md-6">
    <div class="panel">
      <div class="heading">
        <h2 class="title engrave">URL Find &amp; Replace</h2>
      </div>
      <div class="body">

        <form method="post" action="<?php echo $form_path ?>">
          <?php foreach($fields as $field): ?>
            <label for="<?php echo $field["name"] ?>">
              <?php echo $field["label"] ?>
              <?php if($field["req"]): ?><sup title="Required Field">*</sup><?php endif; ?>
            </label>
            <div><input class="form-field <?php $field["req"] ? "required" : ""?>" type="text" id="host" name="<?php echo $field["name"] ?>" value="<?php echo $this->htmlSafe($field["value"]) ?>" /></div>
          <?php endforeach; ?>

          <div class="row">
            <button class="pull-right btn-primary" type="submit" id="submit" name="submit">Next &raquo;</button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>