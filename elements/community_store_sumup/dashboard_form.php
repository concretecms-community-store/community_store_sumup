<?php defined('C5_EXECUTE') or die(_("Access Denied."));
extract($vars);
?>
<div class="form-group">
    <?= $form->label('sumupCurrency', t('Currency'))?>
    <?= $form->select('sumupCurrency', $sumupCurrencies, $sumupCurrency)?>
</div>

<div class="form-group">
    <?= $form->label('sumupShowZip', t('Show Zip (required in US)'))?>
    <?= $form->select('sumupShowZip', [0=>t('No'), 1=>t('Yes')], $sumupShowZip)?>
</div>

<div class="form-group">
    <?= $form->label('sumupPayToEmail', t('Pay To Email'))?>
    <input type="email" name="sumupPayToEmail" value="<?= $sumupPayToEmail?>" class="form-control">
</div>

<div class="form-group">
    <?= $form->label('sumupClientID', t('Client ID'))?>
    <input type="text" name="sumupClientID" value="<?= $sumupClientID?>" class="form-control">
</div>

<div class="form-group">
    <?= $form->label('sumupClientSecret', t('Client Secret'))?>
    <input type="text" name="sumupClientSecret" value="<?= $sumupClientSecret?>" class="form-control">
</div>

<div class="form-group">
    <?= $form->label('sumupAuthorizationCode', t('Refresh Code'))?>
    <input type="text" disabled="disabled"  value="<?= $sumupRefreshToken ? t('saved') : t('Not found - perform Authorization flow') ?>" class="form-control">
</div>

<?php
$authreturn = \Concrete\Core\Support\Facade\Url::to('/sumupauthreturn');

if ($sumupClientID && $sumupClientSecret) {
    $state = time();

    $url = 'https://api.sumup.com/authorize?response_type=code&client_id='.$sumupClientID . '&redirect_uri='. urlencode($authreturn). '&scope=payments&state=ABC123';
    ?>
<p><a href="<?= $url; ?>" target="_blank"><?= t('Start Authorization code flow'); ?></a></p>
    <br />
<?php } ?>

<p><?= t('Use the URL %s as the \'Authorized redirect URL\'', '<strong>' . $authreturn . '</strong>'); ?></p>
<br />
