<div class="row view_database">
  <div class="col-md-6">
    <div class="panel">
      <div class="heading">
        <h2 class="title engrave">WordPress Config File</h2>
      </div>
      <div class="body">
        <h3>File Path</h3>
        <p><?php echo $this->htmlEncode($config["file"]->getPath()) ?></p>
        <h3>Database Settings</h3>

        <ul>
          <li>
            <strong><code>Constants</code></strong>
            <ul>
              <?php foreach($config["constants"] as $name => $value): ?>
              <li><code><?php echo str_replace(" ", "&nbsp;", str_pad($this->htmlEncode($name), 13, " ")) ?> &rarr; <?php echo $this->htmlEncode($value) ?></code></li>
              <?php endforeach; ?>
            </ul>
          </li>
          <li>
            <strong><code>Variables</code></strong>
            <ul>
              <?php foreach($config["variables"] as $name => $value): ?>
              <li><code>$<?php echo str_replace(" ", "&nbsp;", str_pad($this->htmlEncode($name), 12, " ")) ?> &rarr; <?php echo $this->htmlEncode($value) ?></code></li>
              <?php endforeach; ?>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="panel">
      <div class="heading">
        <h2 class="title engrave">Database Settings</h2>
      </div>
      <div class="body">
        <form method="post" action="<?php echo $form_path ?>">
          <?php foreach($fields as $field): ?>
            <label for="<?php echo $field["name"] ?>">
              <?php echo $field["label"] ?>
              <?php if($field["req"]): ?><sup title="Required Field">*</sup><?php endif; ?>
            </label>
            <div><input class="form-field <?php $field["req"] ? "required" : ""?>" type="text" id="host" name="<?php echo $field["name"] ?>" value="<?php echo $this->htmlEncode($field["value"]) ?>" /></div>
          <?php endforeach; ?>

            <div class="row">
              <div class="col-md-12">
                <button class="pull-right btn-primary" type="submit" id="submit" name="submit">Save &amp; Next &raquo;</button>
              </div>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>