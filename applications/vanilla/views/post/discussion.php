<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();

$CancelUrl = $this->data('_CancelUrl');
if (!$CancelUrl) {
    $CancelUrl = '/discussions';
    if (c('Vanilla.Categories.Use') && is_object($this->Category))
        $CancelUrl = '/categories/'.urlencode($this->Category->UrlCode);
}

?>
<div id="DiscussionForm" class="FormTitleWrapper DiscussionForm">
    <?php
    if ($this->deliveryType() == DELIVERY_TYPE_ALL)
        echo wrap($this->data('Title'), 'h1', array('class' => 'H'));

    echo '<div class="FormWrapper">';
    echo $this->Form->open();
    echo $this->Form->errors();
    $this->fireEvent('BeforeFormInputs');

    if ($this->ShowCategorySelector === TRUE) {
        echo '<div class="P">';
        echo '<div class="Category">';
        echo $this->Form->label('Category', 'CategoryID'), ' ';
        echo $this->Form->CategoryDropDown('CategoryID', array('Value' => val('CategoryID', $this->Category), 'IncludeNull' => TRUE));
        echo '</div>';
        echo '</div>';
    }

    echo '<div class="P">';
    echo $this->Form->label('Discussion Title', 'Name');
    echo wrap($this->Form->textBox('Name', array('maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
    echo '</div>';

    $this->fireEvent('BeforeBodyInput');
    echo '<div class="P">';
    echo $this->Form->bodyBox('Body', array('Table' => 'Discussion', 'FileUpload' => true));

    //	      echo wrap($this->Form->textBox('Body', array('MultiLine' => true, 'format' => $this->data('Discussion.Format'))), 'div', array('class' => 'TextBoxWrapper'));
    echo '</div>';

    $Options = '';
    // If the user has any of the following permissions (regardless of junction), show the options
    // Note: I need to validate that they have permission in the specified category on the back-end
    // TODO: hide these boxes depending on which category is selected in the dropdown above.
    if ($Session->checkPermission('Vanilla.Discussions.Announce')) {
        $Options .= '<li>'.CheckOrRadio('Announce', 'Announce', $this->data('_AnnounceOptions')).'</li>';
    }

    //      if ($Session->checkPermission('Vanilla.Discussions.Close'))
    //         $Options .= '<li>'.$this->Form->CheckBox('Closed', t('Close'), array('value' => '1')).'</li>';

    $this->EventArguments['Options'] = &$Options;
    $this->fireEvent('DiscussionFormOptions');

    if ($Options != '') {
        echo '<div class="P">';
        echo '<ul class="List Inline PostOptions">'.$Options.'</ul>';
        echo '</div>';
    }

    $this->fireEvent('AfterDiscussionFormOptions');

    echo '<div class="Buttons">';
    $this->fireEvent('BeforeFormButtons');
    echo $this->Form->button((property_exists($this, 'Discussion')) ? 'Save' : 'Post Discussion', array('class' => 'Button Primary DiscussionButton'));
    if (!property_exists($this, 'Discussion') || !is_object($this->Discussion) || (property_exists($this, 'Draft') && is_object($this->Draft))) {
        echo $this->Form->button('Save Draft', array('class' => 'Button DraftButton'));
    }
    echo $this->Form->button('Preview', array('class' => 'Button PreviewButton'));
    echo ' '.anchor(t('Edit'), '#', 'Button WriteButton Hidden')."\n";
    $this->fireEvent('AfterFormButtons');
    echo anchor(t('Cancel'), $CancelUrl, 'Button Cancel');
    echo '</div>';


    echo $this->Form->close();
    echo '</div>';
    ?>
</div>
