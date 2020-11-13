<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo t($this->Data['Title']); ?></h1>

<?php
echo $this->Form->open();
echo $this->Form->errors();
$pointsAwardEnabled = (bool)c('QnA.Points.Enabled');
$textBoxAttributes = [];
if (!$pointsAwardEnabled) {
    $pointsAwardChildrenAttributes['disabled'] = 'disabled';
}

$featureFollowUpEnabled = (bool)c('Feature.QnAFollowUp.Enabled');
if (!$featureFollowUpEnabled) {
    $featureFollowUpChildrenAttributes['disabled'] = 'disabled';
}
?>
<ul>
    <li class="form-group"><?php
        echo $this->Form->toggle(
                'QnA.Points.Enabled',
                t('Enables points award. This will gives users points for answering questions.'),
                [
                    'id' => 'IsPointsAwardEnabled',
                    'data-children' => 'js-point-awards-inputs'
                ]
        );
    ?></li>
    <li class="form-group js-point-awards-inputs" <?php echo $pointsAwardEnabled ? '' : ' style="display:none;"'?>><?php
        echo $this->Form->labelWrap(t('Point(s) per answer (Only the user\'s first answer to a question will award points)'), 'QnA.Points.Answer');
        echo $this->Form->textBoxWrap('QnA.Points.Answer', $pointsAwardChildrenAttributes);
    ?></li>
    <li class="form-group js-point-awards-inputs" <?php echo $pointsAwardEnabled ? '' : ' style="display:none;"'?> ><?php
        echo $this->Form->labelWrap(t('Points per accepted answer'), 'QnA.Points.AcceptedAnswer');
        echo $this->Form->textBoxWrap('QnA.Points.AcceptedAnswer', $pointsAwardChildrenAttributes);
    ?></li>
<!--    Hide toggle until this feature is ready for release -->
    <li class="form-group">
        <?php
            echo $this->Form->toggle(
                'Feature.QnAFollowUp.Enabled',
                t('Enable Follow-up Notifications'),
                ['id' => 'IsQnAFollowUpEnabled', 'data-children' => 'js-feature-followup'],
                t('QnAFollowup.Feature.Enabled',"This feature sends emails to the authors of answered questions that do not have an accepted answer.")
            );
        ?>
    </li>
    <li class="form-group js-feature-followup" <?php echo $featureFollowUpEnabled ? '' : ' style="display:none;"'?> >
        <div class="label-wrap">
            <?php
                echo $this->Form->label(t('Follow Up Delay'), 'QnA.FollowUp.Interval');
                echo '<div class="info">'.t('Number of days before a follow-up email is automatically sent.').'</div>';
            ?>
        </div>
        <?php echo $this->Form->textBoxWrap('QnA.FollowUp.Interval', $featureFollowUpChildrenAttributes); ?>
    </li>
</ul>
<?php echo $this->Form->close('Save'); ?>
