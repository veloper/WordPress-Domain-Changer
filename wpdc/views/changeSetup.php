<div class="row view_change_setup">
  <form name="change" method="post" action="<?php echo $form_path; ?>">
    <div class="col-md-7">
      <div class="panel">
        <div class="heading">
          <h2 class="title engrave">Selected Tables</h2>
        </div>
        <div class="body">
          <div class="table-responsive">
            <table class="table table-striped table-bordered table-condensed">
              <thead>
                <tr>
                  <th width="100" style="text-align:center">Prefix</th>
                  <th style="text-align:center">Name</th>
                  <th width="100">Rows</th>
                </tr>
              </head>
              <tbody>
              <?php foreach($tables as $table): ?>
                <tr>
                  <td align="right"><?php echo $table_prefix ?></td>
                  <td><?php echo str_replace($table_prefix, "", $table->name) ?></td>
                  <td><?php echo $table->getRowCount() ?></td>
                </tr>
              <?php endforeach ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-5">
      <div class="panel">
        <div class="heading">
          <h2 class="title engrave">Find &amp; Replace</h2>
        </div>
        <div class="body">

            <?php foreach($fields as $field): ?>
              <label for="<?php echo $field["name"] ?>">
                <?php echo $field["label"] ?>
                <?php if($field["req"]): ?><sup title="Required Field">*</sup><?php endif; ?>
              </label>
              <div><input class="form-field <?php $field["req"] ? "required" : ""?>" type="text" id="host" name="<?php echo $field["name"] ?>" value="<?php echo $this->htmlEncode($field["value"]) ?>" /></div>
            <?php endforeach; ?>

            <div class="row">
              <div class="col-md-12">
                <button class="pull-right btn-primary" type="submit" id="submit" name="submit">Find &amp; Review Changes &raquo;</button>
              </div>
            </div>
        </div>

      </div>
    </div>

  </form>
</div>