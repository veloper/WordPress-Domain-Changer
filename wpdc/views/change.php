<div class="view_change">
  <form name="change" method="POST" action="<?php echo $form_path; ?>">
    <div class="col-md-7">
      <div class="panel">
        <div class="heading">
          <h2 class="title engrave">Tables &amp; Columns</h2>
        </div>
        <div class="body">
          <div class="table-responsive">
            <table class="table table-striped table-bordered table-condensed">
              <thead>
                <tr>
                  <th class="checkbox"></th>
                  <th>Table</th>
                  <th>Rows</th>
                  <th>Text-<em>ish</em> Columns</th>
                </tr>
              </head>
              <tbody>
                <tr>
                  <td colspan="4" align="center" class="info"><em><strong>Note:</strong> Table prefix </em> <code>"<?php echo $this->htmlEncode($table_prefix) ?>"</code> <em> hidden for readability.</em></td>
                </tr>
              <?php foreach($tables as $table): ?>
                <tr>
                  <td class="checkbox"><input type="checkbox" name="table_<?php echo $table->name ?>" value="1" checked /></td>
                  <td><code><?php echo str_replace($table_prefix, "", $table->name) ?></code></td>
                  <td align="right"><?php echo $table->getRowCount() ?></td>
                  <td>
                    <?php foreach($table->getStringishColumns() as $column): ?>
                      <span class="label label-default"><code><?php echo $column->name ?></code></span>
                    <?php endforeach ?>
                  </td>
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
          <h2 class="title engrave">URL Find &amp; Replace</h2>
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
                <button class="pull-right btn-primary" type="submit" id="submit" name="submit">Preview Changes &raquo;</button>
              </div>
            </div>
        </div>

      </div>
    </div>

  </form>
</div>