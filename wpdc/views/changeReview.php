<div class="view_change_review">
  <form name="change" method="post">
    <div class="col-md-12">
      <div class="panel">
        <div class="heading">
          <h2 class="title engrave">Review Database Changes</h2>
        </div>
        <div class="body">
          <table class="table table-striped table-bordered table-condensed">
            <thead>
              <tr>
                <th class="checkbox"></th>
                <th>Table</th>
                <th>Fields</th>
                <th>Queries</th>
              </tr>
            </head>
            <tbody>

            <?php foreach($results as $result): ?>
              <tr>
                <td class="checkbox"><input type="checkbox" name="" value="1" checked /></td>
                <td><code><?php echo $result["table"]->name; ?></code></td>
                <td>
                  <?php foreach($result["stringish_columns"] as $column): ?>
                    <span class="label label-default"><code><?php echo $column->name ?></code></span>
                  <?php endforeach ?>
                </td>
                <td align="right"><pre><?php echo count($result["alterations"]) ?></pre></td>
              </tr>
            <?php endforeach; ?>

            </tbody>
          </table>
          <div>
            <a class="btn-primary">Confirm &amp; Execute Queries</a>
          </div>
      </div>
    </div>
  </form>
</div>