<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo t($this->Data['Title']); ?></h1>

<?php
echo $this->Form->open();
echo $this->Form->errors();
$pointsAwardEnabled = c('QnA.Points.Enabled');
$textBoxAttributes = [];
$checkBoxAttributes = [
    'id' => 'IsPointsAwardEnabled',
];
if ($pointsAwardEnabled) {
    $checkBoxAttributes['checked'] = true;
} else {
    $textBoxAttributes['disabled'] = true;
}
?>
<ul>
    <li class="form-group"><?php
        echo $this->Form->toggle('QnA.Points.Enabled', t('Enables points award. This will gives users points for answering questions.'), $checkBoxAttributes);
    ?></li>
    <li class="form-group js-point-awards-inputs"<?php echo $pointsAwardEnabled ? null : ' style="display:none;"'?>><?php
        echo $this->Form->labelWrap(t('Point(s) per answer (Only the user\'s first answer to a question will award points)'), 'QnA.Points.Answer');
        echo $this->Form->textBoxWrap('QnA.Points.Answer', $textBoxAttributes);
    ?></li>
    <li class="form-group js-point-awards-inputs"<?php echo $pointsAwardEnabled ? null : ' style="display:none;"'?>><?php
        echo $this->Form->labelWrap(t('Points per accepted answer'), 'QnA.Points.AcceptedAnswer');
        echo $this->Form->textBoxWrap('QnA.Points.AcceptedAnswer', $textBoxAttributes);
    ?></li>
</ul>
<?php echo $this->Form->close('Save'); ?>
