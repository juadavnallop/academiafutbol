<?php foreach($fields as $key=>[$label,$type,$required,$rule]): $value=$values[$key]??''; ?>
<div class="field"><label for="<?= e($key) ?>"><?= e($label) ?><?= $required?' *':'' ?></label>
<?php $attributes=($required?' required':'').(isset($errors[$key])?' aria-invalid="true" aria-describedby="err-'.e($key).'"':''); ?>
<?php if($type==='select'): ?><select name="<?= e($key) ?>" id="<?= e($key) ?>"<?= $attributes ?>><option value="">Sin especificar</option><?php foreach($rule as $k=>$text): ?><option value="<?= e($k) ?>" <?= (string)$value===(string)$k?'selected':'' ?>><?= e($text) ?></option><?php endforeach; ?></select>
<?php elseif($type==='textarea'): ?><textarea name="<?= e($key) ?>" id="<?= e($key) ?>" maxlength="<?= e($rule) ?>" rows="4"<?= $attributes ?>><?= e($value) ?></textarea>
<?php else: ?><input name="<?= e($key) ?>" id="<?= e($key) ?>" type="<?= e($type==='decimal'?'number':$type) ?>" value="<?= e($value) ?>"<?= $attributes ?> <?php if(in_array($type,['number','decimal'],true)): ?>min="<?= e($rule[0]) ?>" max="<?= e($rule[1]) ?>" step="<?= $type==='decimal'?'0.01':'1' ?>"<?php else: ?>maxlength="<?= e($rule) ?>"<?php endif; ?>><?php endif; ?>
<?php if(isset($errors[$key])): ?><small class="field-error" id="err-<?= e($key) ?>"><?= e($errors[$key]) ?></small><?php endif; ?></div>
<?php endforeach; ?>
