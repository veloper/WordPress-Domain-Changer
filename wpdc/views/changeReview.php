<div class="row view_change_review">
  <form name="change" method="post" action="<?php echo $form_path; ?>" onSubmit="return confirm('Are you sure?');">
    <div class="col-md-12">
      <div class="panel">
        <div class="heading">
          <h2 class="title engrave">Review &amp; Confirm Changes</h2>
        </div>
        <div class="body">
          <table class="table table-bordered table-condensed">
            <thead>
              <tr>
                <th>Table</th>
                <th>Searched Fields</th>
                <th width="100">Queries</th>
              </tr>
            </head>
            <tbody>

            <?php foreach($results as $result): ?>
              <tr>
                <td><code><?php echo $result["table"]->name; ?></code></td>
                <td>
                  <?php foreach($result["table"]->getStringishColumns() as $column): ?>
                    <span class="label label-info"><code><?php echo $column->name ?></code></span>
                  <?php endforeach ?>
                </td>
                <td align="right"><a href="javascript:void(0)" onClick="$(this).parents('tr').next().find('td').toggle()" title="Show/Hide Queries"><?php echo count($result["alterations"]) ?></a></td>
              </tr>
              <tr>
                <td colspan="3" style="display:none">
                  <div class="sql">
                    <ol>
                    <?php foreach($result["alterations"] as $i => $alteration): ?>
                      <li<?php if($i & 1) echo " class='odd'"?>>
                        <?php
                          $output = $this->htmlEncodeSql($alteration->toSql());
                          if($alteration->isSerialized()) $output = $this->highlight($alteration->replace, $output);
                          echo $output;
                        ?>
                      </li>
                    <?php endforeach ?>
                    </ol>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <div class="row">
            <div class="col-md-12">
              <button type="submit" class="pull-right btn-primary btn-danger">Confirm &amp; Apply Changes &raquo;</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>
