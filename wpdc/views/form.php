<div id="timeout">
    <div>You have <span id="seconds"><?php echo ((int) $_COOKIE[DDWPDC_COOKIE_NAME_EXPIRE] + 5) - time(); ?></span> Seconds left in this session.</div>
    <div id="bar"></div>
</div>
<div class="clear"></div>
<div id="left">
    <form method="post" action="<?php echo basename(__FILE__); ?>">
        <h3>Database Connection Settings</h3>
        <blockquote>
            <?php
            // Try to Auto-Detect Settings from wp-config.php file and pre-populate fields.
            if($DDWPDC->isConfigLoaded()) $DDWPDC->actions[] = 'Attempting to auto-detect form field values.';
            ?>
            <label for="host">Host</label>
            <div><input type="text" id="host" name="host" value="<?php echo $DDWPDC->getConfigConstant('DB_HOST'); ?>" /></div>

            <label for="username">User</label>
            <div><input type="text" id="username" name="username" value="<?php echo $DDWPDC->getConfigConstant('DB_USER'); ?>" /></div>

            <label for="password">Password</label>
            <div><input type="text" id="password" name="password" value="<?php echo $DDWPDC->getConfigConstant('DB_PASSWORD'); ?>" /></div>

            <label for="database">Database Name</label>
            <div><input type="text" id="database" name="database" value="<?php echo $DDWPDC->getConfigConstant('DB_NAME'); ?>" /></div>

            <label for="prefix">Table Prefix</label>
            <div><input type="text" id="prefix" name="prefix" value="<?php echo $DDWPDC->getConfigTablePrefix(); ?>" /></div>
        </blockquote>

        <label for="old_domain">Old Domain</label>
        <div>http://<input type="text" id="old_domain" name="old_domain" value="<?php echo $DDWPDC->getOldDomain(); ?>" /></div>

        <label for="new_domain">New Domain</label>
        <div>http://<input type="text" id="new_domain" name="new_domain" value="<?php echo $DDWPDC->getNewDomain(); ?>" /></div>

        <input type="submit" id="submit_button" name="submit_button" value="Change Domain!" />
    </form>
</div>
<div id="right">
    <?php if(count($DDWPDC->errors) > 0): foreach($DDWPDC->errors as $error): ?>
        <div class="log error"><strong>Error:</strong> <?php echo $error; ?></div>
    <?php endforeach; endif; ?>

    <?php if(count($DDWPDC->notices) > 0): foreach($DDWPDC->notices as $notice): ?>
        <div class="log notice"><strong>Notice:</strong> <?php echo $notice; ?></div>
    <?php endforeach; endif; ?>

    <?php if(count($DDWPDC->actions) > 0): foreach($DDWPDC->actions as $action): ?>
        <div class="log action"><strong>Action: </strong><?php echo $action; ?></div>
    <?php endforeach; endif; ?>
</div>
