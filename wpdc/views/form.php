<div class="col-md-6">

  <div class="panel">
    <div class="heading">
      <h2 class="title engrave">WordPress Config File</h2>
    </div>
    <div class="body">
      <h3>File Path</h3>
      <p>&rarr; <?php echo $this->htmlSafe($config["file"]->getPath()) ?></p>
      <h3>Database Settings <a href="javascript:void(0)" class="btn-primary btn-small">Use These Settings &raquo;</a></h3>
      <p>&rarr; <strong><code>Constants</code></strong></p>
      <ul>
        <?php foreach($config["constants"] as $name => $value): ?>
        <li><code><?php echo str_replace(" ", "&nbsp;", str_pad($this->htmlSafe($name), 13, " ")) ?> &rarr; <?php echo $this->htmlSafe($value) ?></code></li>
        <?php endforeach; ?>
      </ul>

      <p>&rarr; <strong><code>Variables</code></strong></p>
      <ul>
        <?php foreach($config["variables"] as $name => $value): ?>
        <li><code>$<?php echo str_replace(" ", "&nbsp;", str_pad($this->htmlSafe($name), 12, " ")) ?> &rarr; <?php echo $this->htmlSafe($value) ?></code></li>
        <?php endforeach; ?>
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
          <label for="<?php echo $field["name"] ?>"><?php echo $field["label"] ?></label>
          <div><input class="form-field" type="text" id="host" name="<?php echo $field["name"] ?>" value="<?php echo $this->htmlSafe($field["value"]) ?>" /></div>
        <?php endforeach; ?>

        <div class="row">
          <button class="pull-right btn-primary" type="submit" id="submit" name="submit">Next &raquo;</button>
        </div>
      </form>
    </div>
  </div>
</div>



