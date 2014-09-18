<div class="view_change">
  <form name="change" method="POST" action="<?php echo $form_path; ?>">
    <div class="col-md-7">
      <div class="panel">
        <div class="heading">
          <h2 class="title engrave">Available Tables</h2>
        </div>
        <div class="body">
          <div class="table-responsive">
            <table class="table table-striped table-bordered table-condensed">
              <thead>
                <tr>
                  <th class="checkbox"></th>
                  <th width="100" style="text-align:center">Prefix</th>
                  <th style="text-align:center">Name</th>
                  <th width="100">Rows</th>
                </tr>
              </head>
              <tbody>
              <?php foreach($tables as $table): ?>
                <tr>
                  <td class="checkbox"><input type="checkbox" name="table_<?php echo $table->name ?>" value="1" <?php echo in_array($table->name, $selected_table_names) ? "checked" : "" ?> /></td>
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
          <h2 class="title engrave">Selected Tables</h2>
        </div>
        <div class="body">
          <div class="row">
            <div class="col-md-12">
              <ul id="selected_tables">
              </ul>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12">
              <button class="pull-right btn-primary" type="submit" id="submit" name="submit">Save &amp; Next &raquo;</button>
            </div>
          </div>

        </div>

      </div>
    </div>

  </form>
</div>