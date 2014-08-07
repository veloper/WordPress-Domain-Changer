<style type="text/css">
  form {
    margin: 25px 0;
    text-align: center;
  }
  form label, form input, form button {
  }
  form input {
    width:300px;
  }
  #body {
    margin:0 auto;
    width:450px;
    border-radius: 5px;
  }
</style>

<div class="view">
  <div class="heading">
    <h2 class="title engrave">Authenticate</h2>
  </div>

  <form name="login" method="post" action="<?php echo $form_path; ?>">
    <input type="password" class="form-field" name="auth_password" value="" placeholder="Enter Password..." <?php echo $is_default_password_set ? "disabled" : "" ?>/>
    <button class="btn-primary" type="submit" name="submit_button"  <?php echo $is_default_password_set ? "disabled" : "" ?>/>Login</button>
  </form>
</div>