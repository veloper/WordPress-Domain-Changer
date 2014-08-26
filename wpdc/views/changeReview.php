<div class="view_change_review">
  <form name="change" method="post" action="<?php echo ""; ?>">
    <div class="col-md-12">
      <div class="panel">
        <div class="heading">
          <h2 class="title engrave">Review Changes</h2>
        </div>
        <div class="body">
          <div class="table-responsive">
            <table class="table table-striped table-bordered table-condensed">
              <thead>
                <tr>
                  <th>Record</th>
                </tr>
              </head>
              <tbody>
              <?php foreach($records as $record): ?>
                <tr>
                  <td><pre><?php print_r($record->attributes); ?></pre></td>
                </tr>
              <?php endforeach ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>


  </form>
</div>