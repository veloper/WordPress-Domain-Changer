<?php
$alterations_exist = (count($results) > 0);
?>
<div class="view_change_review">
  <form name="change" method="post" action="<?php echo $form_path; ?>" onSubmit="return confirm('Are you sure?');">
    <div class="col-md-12">
      <div class="panel">
        <div class="heading">
          <h2 class="title engrave">Review &amp; Confirm Changes</h2>
        </div>
        <div class="body">
          <table class="table table-striped table-bordered table-condensed">
            <thead>
              <tr>
                <th>Table</th>
                <th>Searched Fields</th>
                <th>Queries</th>
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
                <td align="right"><?php echo count($result["alterations"]) ?></td>
              </tr>
              <tr>
                <td colspan="3">
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
              <?php if($alterations_exist): ?>
                <button type="submit" class="pull-right btn-primary btn-danger">Confirm &amp; Apply Changes &raquo;</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>
