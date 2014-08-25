<div class="view_change_review">
  <form name="change" method="post" action="<?php echo $form_path; ?>">
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
                  <th class="checkbox"></th>
                  <th>Table</th>
                  <th>Rows</th>
                  <th>Text-<em>ish</em> Columns</th>
                </tr>
              </head>
              <tbody>
              <?php foreach($tables as $table): ?>
              <?php endforeach ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>


  </form>
</div>