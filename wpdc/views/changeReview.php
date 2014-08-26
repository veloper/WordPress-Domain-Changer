<style type="text/css">
  td {
    font-size:0.8em;
  }
  ins, del {
    font-weight: bold;
  }
</style>

$('td span').each( function(){ if($(this).html().length > 20) $(this).html($(this).html().substr(1, 20) + "...\n" + $(this).html().substr(-20)) } )


<div class="view_change_review">
  <form name="change" method="post" action="<?php echo ""; ?>">
    <div class="col-md-12">
      <div class="panel">
        <div class="heading">
          <h2 class="title engrave">Review Table Changes</h2>
        </div>
        <div class="body">
          <div class="row">
            <div class="col-md-12">

              <?php foreach($table_alterations as $table => $alterations): ?>

              <div class="panel">
                <div class="heading">
                  <h2 class="title engrave"><?php echo $table ?></h2>
                </div>
                <div class="body">
                  <div class="table-responsive">
                    <table class="table table-striped table-bordered table-condensed">
                      <thead>
                        <tr>
                          <th class="checkbox"></th>
                          <th>Primary Key</th>
                          <th>Affected Field</th>
                          <th>Diff</th>
                        </tr>
                      </head>
                      <tbody>

                      <?php foreach($alterations as $alteration): ?>

                        <tr>
                          <td class="checkbox"><input type="checkbox" name="" value="1" checked /></td>
                          <td><pre><?php echo $alteration->record->getPrettyPrimaryKeyAttributes() ?></pre></td>
                          <td><pre><?php echo $alteration->getColumnName() ?></pre></td>
                          <td><pre><?php echo $alteration->getDiff() ?></pre></td>
                        </tr>

                      <?php endforeach ?>

                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>