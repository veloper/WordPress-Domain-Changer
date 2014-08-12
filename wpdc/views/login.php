<style type="text/css">
  form {
    margin: 25px 0;
    text-align: center;
  }
  form input {
    width:300px !important;
  }
</style>

<div class="col-md-6 col-md-offset-3">

  <div class="panel">
    <div class="heading">
      <h2 class="title engrave">Authenticate</h2>
    </div>
    <div class="body">
      <form name="login" method="post" action="<?php echo $form_path; ?>">
        <input type="password" class="form-field" name="password" value="" placeholder="Enter Password..." <?php echo $disabled ? "disabled" : "" ?>/>
        <button class="btn-primary" type="submit" name="submit"  <?php echo $disabled ? "disabled" : "" ?>>Login</button>
      </form>
    </div>
  </div>

</div>
