<?php defined('C5_EXECUTE') or die(_("Access Denied.")); 
extract($vars);
?>
<div class="form-group">
    <?=$form->label('authorizeNetCurrency',t('Currency'))?>
    <?=$form->select('authorizeNetCurrency',$authorizeNetCurrencies,$authorizeNetCurrency)?>
</div>

<div class="form-group">
    <?=$form->label('authorizeNetMode',t('Mode'))?>
    <?=$form->select('authorizeNetMode',array('test'=>t('Test'), 'live'=>t('Live')),$authorizeNetMode)?>
</div>

<div class="form-group">
    <label><?=t("Test Login ID")?></label>
    <input type="text" name="authorizeNetTestLoginID" value="<?= $authorizeNetTestLoginID?>" class="form-control">
</div>

<div class="form-group">
    <label><?=t("Test Client Key")?></label>
    <input type="text" name="authorizeNetTestClientKey" value="<?= $authorizeNetTestClientKey?>" class="form-control">
</div>

<div class="form-group">
    <label><?=t("Test Transaction Key")?></label>
    <input type="text" name="authorizeNetTestTransactionKey" value="<?= $authorizeNetTestTransactionKey?>" class="form-control">
</div>

<div class="form-group">
    <label><?=t("Live Login ID")?></label>
    <input type="text" name="authorizeNetLiveLoginID" value="<?= $authorizeNetLiveLoginID?>" class="form-control">
</div>

<div class="form-group">
    <label><?=t("Live Client Key")?></label>
    <input type="text" name="authorizeNetLiveClientKey" value="<?= $authorizeNetLiveClientKey?>" class="form-control">
</div>

<div class="form-group">
    <label><?=t("Live Transaction Key")?></label>
    <input type="text" name="authorizeNetLiveTransactionKey" value="<?= $authorizeNetLiveTransactionKey?>" class="form-control">
</div>


