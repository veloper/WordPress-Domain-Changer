<!DOCTYPE html>
<html>
  <head>
    <title>WordPress Domain Changer by Daniel Doezema</title>
    <link rel="stylesheet" type="text/css" href="<?php echo $request->assets_url; ?>/application.css"></link>
  </head>
  <body>
    <div id="header">
      <div class="container">
        <div class="row">
          <div class="col-md-8">
            <div class="inner engrave">
              <h1>WordPress Domain Changer <iframe src="http://ghbtns.com/github-btn.html?user=veloper&repo=WordPress-Domain-Changer&type=watch&count=true" allowtransparency="true" frameborder="0" scrolling="0" width="110px" height="20px"></iframe></h1>
              <em>By <a href="http://dan.doezema.com" target="_blank">Daniel Doezema</a></em>
            </div>
          </div>
          <?php if($request->authenticated) : ?>
              <div class="col-md-3">
                <div class="header-block">
                  <div class="session">
                    <span class="title">Session Status</span>
                    <span class="timer"><span class="minutes"></span>:<span class="seconds"></span> Remaining</span>
                    <div class="green"></div>
                  </div>
                </div>
              </div>
              <div class="col-md-1">
                <div class="header-block">
                  <a class="btn-primary pull-right" href="<?php echo $logout_path; ?>">Logout</a>
                </div>
              </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div id="main">

      <div class="container">

        <?php if($this->getFlashMessages()) : ?>
          <div class="row">
            <div id="flash" class="col-md-12">
              <?php foreach ( $this->getFlashMessages() as $flash ): ?>
              <?php $is_dismissable = !in_array($flash['type'], array("success")); ?>
              <div class="message <?php echo $flash['type'] ?> <?php if($is_dismissable) echo "dismissable" ?>">
                <?php if($is_dismissable) : ?> <a class="pull-right dismiss">&times;</a> <?php endif; ?>
                <?php echo $flash['message'] ?>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="row">
          <?php echo $body; ?>
        </div>
      </div>

    </div>
    <script src="<?php echo $request->assets_url; ?>/application.js" type="text/javascript" language="Javascript"></script>
  </body>
</html>
