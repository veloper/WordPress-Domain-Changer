<!DOCTYPE html>
<html>
  <head>
    <title>WordPress Domain Changer by Daniel Doezema </title>
    <link rel="stylesheet" type="text/css" href="assets/application.css"></link>
  </head>
  <body>
    <div id="header">
      <div class="inner engrave">
        <h1>WordPress Domain Changer <iframe src="http://ghbtns.com/github-btn.html?user=veloper&repo=WordPress-Domain-Changer&type=watch&count=true" allowtransparency="true" frameborder="0" scrolling="0" width="110px" height="20px"></iframe></h1>
        <em>By <a href="http://dan.doezema.com" target="_blank">Daniel Doezema</a></em>
      </div>
    </div>

    <div id="main">

      <div id="flash">
        <?php foreach ( $this->getFlashMessages() as $flash ): ?>
        <div class="<?php echo $flash['type'] ?>">
          <?php echo $flash['message'] ?>
        </div>
        <?php endforeach; ?>
      </div>

      <div id="body">
        <?php echo $body; ?>
      </div>

    </div>
    <script src="assets/application.js" type="text/javascript" language="Javascript"></script>
  </body>
</html>
