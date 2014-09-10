<?php
$alterations_exist = (count($results) > 0);
?>
<div class="view_change_review">
  <form name="change" method="post">
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
                <th>Text-<em>ish</em> Columns</th>
                <th width="100">Changes</th>
              </tr>
            </head>
            <tbody>

            <?php if($alterations_exist): ?>

              <?php foreach($results as $result): ?>
                <tr>
                  <td><code><?php echo $result["table"]->name; ?></code></td>
                  <td>
                    <?php foreach($result["table"]->getStringishColumns() as $column): ?>
                      <span class="label label-default"><code><?php echo $column->name ?></code></span>
                    <?php endforeach; ?>
                  </td>
                  <td align="right"><pre><?php echo count($result["alterations"]) ?></pre></td>
                </tr>
              <?php endforeach; ?>

            <?php else: ?>
              <tr>
                  <td rowspan="3"></td>


            <?php endif; ?>

            </tbody>
          </table>
          <div class="row">
            <div class="col-md-6">
              <button type="button" class="btn-primary" onclick="window.location=this.getAttribute('data-href')" data-href="<?php echo $this->htmlEncode($back_path); ?>">&laquo; Back</button>
            </div>
            <div class="col-md-6">
              <?php if($alterations_exist): ?>
                <button type="submit" class="pull-right btn-primary btn-danger" onclick="return confirm('Are you sure?');">Confirm &amp; Apply Changes &raquo;</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>