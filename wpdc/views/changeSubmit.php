<div class="row view_change_submit">
  <form name="change" method="post" action="" onSubmit="return confirm('Are you sure?');">
    <div class="col-md-12">
      <div class="panel">
        <div class="heading">
          <h2 class="title engrave">Queries</h2>
        </div>
        <div class="body">
          <table class="table table-striped table-bordered table-condensed">
            <thead>
              <tr>
                <th>Result</th>
                <th>Affected Rows</th>
                <th>Error</th>
                <th>Query</th>
              </tr>
            </head>
            <tbody>

            <?php foreach($query_to_result as $value): ?>
              <tr>
                <td><?php echo $this->indicator($value["result"]) ?></td>
                <td><?php echo $value["affected_rows"] ?></td>
                <td><?php echo $value["error"] ?></td>
                <td>
                  <div class="sql">
                        <?php echo $this->htmlEncodeSql($value["query"]); ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>



        </div>
      </div>
    </div>
  </form>
</div>
